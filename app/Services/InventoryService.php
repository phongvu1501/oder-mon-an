<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Support\BusinessCode;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function record(
        InventoryTransactionType $type,
        array $lines,
        ?string $reason = null,
        ?int $supplierId = null,
        ?int $userId = null,
        ?Model $reference = null,
    ): InventoryTransaction {
        return DB::transaction(function () use ($type, $lines, $reason, $supplierId, $userId, $reference) {
            $lines = collect($lines)->sortBy('inventory_item_id')->values();
            $items = InventoryItem::query()
                ->whereIn('id', $lines->pluck('inventory_item_id'))
                ->lockForUpdate()->get()->keyBy('id');

            if ($lines->isEmpty() || $items->count() !== $lines->pluck('inventory_item_id')->unique()->count()) {
                throw ValidationException::withMessages(['items' => 'Danh sách nguyên liệu không hợp lệ.']);
            }

            $transaction = InventoryTransaction::create([
                'transaction_code' => BusinessCode::make('INV'),
                'type' => $type,
                'supplier_id' => $supplierId,
                'reason' => $reason,
                'created_by' => $userId,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);

            foreach ($lines as $line) {
                $item = $items->get((int) $line['inventory_item_id']);
                $before = Decimal::toMinor($item->current_stock, 3);
                $requested = Decimal::toMinor($line['quantity'], 3);
                $delta = match ($type) {
                    InventoryTransactionType::In, InventoryTransactionType::Return => abs($requested),
                    InventoryTransactionType::Out, InventoryTransactionType::Waste => -abs($requested),
                    InventoryTransactionType::Adjustment => $requested,
                };
                $after = $before + $delta;

                if ($delta === 0 || $after < 0) {
                    throw ValidationException::withMessages(['items' => "Tồn kho {$item->name} không hợp lệ sau giao dịch."]);
                }

                $item->update(['current_stock' => Decimal::fromMinor($after, 3)]);
                $transaction->items()->create([
                    'inventory_item_id' => $item->id,
                    'quantity' => Decimal::fromMinor($delta, 3),
                    'before_quantity' => Decimal::fromMinor($before, 3),
                    'after_quantity' => Decimal::fromMinor($after, 3),
                    'unit_cost' => $line['unit_cost'] ?? null,
                ]);
            }

            return $transaction->load('items.inventoryItem');
        }, 3);
    }

    public function deductForOrder(Order $order, ?int $userId = null): ?InventoryTransaction
    {
        $order->loadMissing('items.product.recipe.items');
        $requirements = [];

        foreach ($order->items as $orderItem) {
            $recipe = $orderItem->product?->recipe;
            if (! $recipe) {
                continue;
            }
            $yield = Decimal::toMinor($recipe->yield_quantity, 3);
            foreach ($recipe->items as $recipeItem) {
                $required = (int) round((Decimal::toMinor($recipeItem->quantity, 3) * $orderItem->quantity * 1000) / $yield);
                $requirements[$recipeItem->inventory_item_id] = ($requirements[$recipeItem->inventory_item_id] ?? 0) + $required;
            }
        }

        if ($requirements === []) {
            return null;
        }

        $lines = collect($requirements)->map(fn (int $quantity, int $itemId) => [
            'inventory_item_id' => $itemId,
            'quantity' => Decimal::fromMinor($quantity, 3),
        ])->values()->all();

        return $this->record(InventoryTransactionType::Out, $lines, "Trừ kho cho {$order->order_code}", userId: $userId, reference: $order);
    }
}
