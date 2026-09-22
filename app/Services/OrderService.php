<?php

namespace App\Services;

use App\Enums\BillStatus;
use App\Enums\OrderItemStatus;
use App\Enums\OrderStatus;
use App\Enums\RecordStatus;
use App\Enums\TableSessionStatus;
use App\Models\Bill;
use App\Models\Order;
use App\Models\Product;
use App\Models\TableSession;
use App\Support\BusinessCode;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function create(TableSession $session, array $lines, string $idempotencyKey, ?string $note = null, ?int $userId = null): Order
    {
        return DB::transaction(function () use ($session, $lines, $idempotencyKey, $note, $userId) {
            $lockedSession = TableSession::query()->lockForUpdate()->findOrFail($session->id);
            if ($lockedSession->status !== TableSessionStatus::Active) {
                throw ValidationException::withMessages(['session' => 'Phiên bàn đã kết thúc.']);
            }

            $existing = Order::query()
                ->where('table_session_id', $lockedSession->id)
                ->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $existing->load('items');
            }

            $productIds = collect($lines)->pluck('product_id')->map(fn ($id) => (int) $id)->unique();
            $products = Product::query()->whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages(['items' => 'Một hoặc nhiều món không còn tồn tại.']);
            }

            $subtotalCents = 0;
            $snapshots = [];
            foreach ($lines as $index => $line) {
                $product = $products->get((int) $line['product_id']);
                if ($product->status !== RecordStatus::Active || ! $product->is_available) {
                    throw ValidationException::withMessages(["items.{$index}.product_id" => "{$product->name} hiện không phục vụ."]);
                }
                $quantity = (int) $line['quantity'];
                $lineCents = Decimal::toMinor($product->price) * $quantity;
                $subtotalCents += $lineCents;
                $snapshots[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => Decimal::fromMinor($lineCents),
                    'note' => $line['note'] ?? null,
                    'status' => OrderItemStatus::Pending,
                ];
            }

            $order = Order::create([
                'order_code' => BusinessCode::make('ORD'),
                'table_session_id' => $lockedSession->id,
                'idempotency_key' => $idempotencyKey,
                'status' => OrderStatus::Pending,
                'subtotal' => Decimal::fromMinor($subtotalCents),
                'total' => Decimal::fromMinor($subtotalCents),
                'customer_note' => $note,
                'created_by' => $userId,
            ]);
            $order->items()->createMany($snapshots);
            $this->recalculateBill($lockedSession);

            return $order->load('items', 'tableSession.restaurantTable');
        }, 3);
    }

    public function transition(Order $order, OrderStatus $next, int $userId, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $next, $userId, $reason) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if (! $locked->status->canTransitionTo($next)) {
                throw ValidationException::withMessages(['status' => "Không thể chuyển từ {$locked->status->value} sang {$next->value}."]);
            }

            $changes = ['status' => $next];
            if ($next === OrderStatus::Confirmed) {
                $changes += ['confirmed_by' => $userId, 'confirmed_at' => now()];
            }
            if ($next === OrderStatus::Preparing && ! $locked->inventory_deducted_at) {
                $transaction = $this->inventory->deductForOrder($locked, $userId);
                if ($transaction) {
                    $changes['inventory_deducted_at'] = now();
                }
            }

            $locked->update($changes);
            $itemStatus = match ($next) {
                OrderStatus::Preparing => OrderItemStatus::Preparing,
                OrderStatus::Ready => OrderItemStatus::Ready,
                OrderStatus::Served, OrderStatus::Completed => OrderItemStatus::Served,
                OrderStatus::Cancelled => OrderItemStatus::Cancelled,
                default => OrderItemStatus::Pending,
            };
            $itemChanges = ['status' => $itemStatus];
            if ($next === OrderStatus::Cancelled) {
                $itemChanges['cancel_reason'] = $reason ?: 'Hủy bởi nhân viên';
            }
            $locked->items()->where('status', '!=', OrderItemStatus::Cancelled)->update($itemChanges);
            if ($next === OrderStatus::Cancelled) {
                $this->recalculateBill($locked->tableSession);
            }

            return $locked->refresh()->load('items', 'tableSession.restaurantTable');
        }, 3);
    }

    public function recalculateBill(TableSession $session): Bill
    {
        $bill = $session->bill()->lockForUpdate()->firstOrFail();
        $subtotal = (string) $session->orders()
            ->where('status', '!=', OrderStatus::Cancelled->value)->sum('subtotal');
        $subtotalCents = Decimal::toMinor($subtotal);
        $discountCents = min(Decimal::toMinor($bill->discount), $subtotalCents);
        $totalCents = max(0, $subtotalCents - $discountCents + Decimal::toMinor($bill->tax));
        $paidCents = Decimal::toMinor($bill->paid_amount);

        $status = $paidCents === 0 ? BillStatus::Unpaid : BillStatus::Partial;
        if ($totalCents > 0 && $paidCents >= $totalCents) {
            $status = BillStatus::Paid;
        }

        $bill->update([
            'subtotal' => Decimal::fromMinor($subtotalCents),
            'total' => Decimal::fromMinor($totalCents),
            'status' => $status,
        ]);

        return $bill->refresh();
    }
}
