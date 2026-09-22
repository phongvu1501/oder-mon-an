<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Enums\RecordStatus;
use App\Models\InventoryBatch;
use App\Models\InventoryItem;
use App\Models\InventoryOrderWarning;
use App\Models\InventoryStock;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Warehouse;
use App\Support\BusinessCode;
use App\Support\Decimal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryMovementService
{
    public function __construct(private readonly InventoryCostService $costs, private readonly UnitConversionService $units) {}

    public function defaultWarehouse(): Warehouse
    {
        return Warehouse::query()->where('code', config('restaurant.inventory.default_warehouse_code'))->where('status', 'active')->firstOrFail();
    }

    public function record(InventoryTransactionType $type, array $lines, ?string $reason = null, ?int $supplierId = null, ?int $userId = null, ?Model $reference = null, ?int $warehouseId = null, ?string $idempotencyKey = null, array $metadata = []): InventoryTransaction
    {
        return DB::transaction(function () use ($type, $lines, $reason, $supplierId, $userId, $reference, $warehouseId, $idempotencyKey, $metadata) {
            if ($idempotencyKey && ($existing = InventoryTransaction::where('idempotency_key', $idempotencyKey)->first())) {
                return $existing->load('items.inventoryItem');
            }
            $warehouseId ??= $this->defaultWarehouse()->id;
            $lines = collect($lines)->sortBy('inventory_item_id')->values();
            if ($type === InventoryTransactionType::Adjustment) {
                $hasPositive = $lines->contains(fn ($line) => (float) $line['quantity'] > 0);
                $hasNegative = $lines->contains(fn ($line) => (float) $line['quantity'] < 0);
                $type = $hasPositive && $hasNegative
                    ? InventoryTransactionType::StocktakeAdjustment
                    : ($hasPositive ? InventoryTransactionType::AdjustmentIn : InventoryTransactionType::AdjustmentOut);
            }
            if ($type === InventoryTransactionType::Return) {
                $type = InventoryTransactionType::CustomerReturn;
            }
            $itemIds = $lines->pluck('inventory_item_id')->map(fn ($id) => (int) $id)->unique();
            $items = InventoryItem::query()->whereIn('id', $itemIds)->lockForUpdate()->get()->keyBy('id');
            if ($lines->isEmpty() || $items->count() !== $itemIds->count()) {
                throw ValidationException::withMessages(['items' => 'Danh s?ch nguy?n li?u kh?ng h?p l?.']);
            }

            $transaction = InventoryTransaction::create([
                'transaction_code' => BusinessCode::make($type->prefix()), 'type' => $type, 'status' => 'confirmed',
                'direction' => $type->direction(), 'warehouse_id' => $warehouseId, 'supplier_id' => $supplierId,
                'reason' => $reason, 'occurred_at' => now(), 'created_by' => $userId, 'confirmed_by' => $userId,
                'confirmed_at' => now(), 'reference_type' => $reference?->getMorphClass(), 'reference_id' => $reference?->getKey(),
                'idempotency_key' => $idempotencyKey, 'metadata' => $metadata ?: null,
            ]);

            foreach ($lines as $index => $line) {
                $item = $items->get((int) $line['inventory_item_id']);
                if ($item->status !== RecordStatus::Active) {
                    throw ValidationException::withMessages(["items.{$index}.inventory_item_id" => "{$item->name} ?ang ng?ng s? d?ng."]);
                }
                $stock = InventoryStock::firstOrCreate(
                    ['warehouse_id' => $warehouseId, 'inventory_item_id' => $item->id],
                    ['current_stock' => $item->stocks()->exists() ? 0 : $item->current_stock, 'average_cost' => $item->cost_price]
                );
                $stock = InventoryStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
                $requested = Decimal::toMinor($line['base_quantity'] ?? $line['quantity'], 3);
                if ($requested === 0) {
                    throw ValidationException::withMessages(["items.{$index}.quantity" => 'S? l??ng ph?i kh?c 0.']);
                }
                $delta = match ($type) {
                    InventoryTransactionType::In, InventoryTransactionType::AdjustmentIn, InventoryTransactionType::TransferIn, InventoryTransactionType::CustomerReturn => abs($requested),
                    InventoryTransactionType::Out, InventoryTransactionType::AdjustmentOut, InventoryTransactionType::TransferOut, InventoryTransactionType::Waste, InventoryTransactionType::ReturnToSupplier => -abs($requested),
                    InventoryTransactionType::StocktakeAdjustment => $requested,
                };
                $before = Decimal::toMinor($stock->current_stock, 3);
                $after = $before + $delta;
                if ($after < 0 && ! config('restaurant.inventory.allow_negative_stock')) {
                    throw ValidationException::withMessages(["items.{$index}.quantity" => "Kh?ng ?? t?n kho {$item->name}."]);
                }

                $unitCost = $stock->average_cost;
                $costSource = 'weighted_average';
                if ($type === InventoryTransactionType::In) {
                    if (! array_key_exists('unit_cost', $line)) {
                        throw ValidationException::withMessages(["items.{$index}.unit_cost" => 'Phi?u nh?p ph?i c? gi? nh?p.']);
                    }
                    $unitCost = Decimal::fromMinor(Decimal::toMinor($line['unit_cost'], 2), 2);
                    $stock->average_cost = $this->costs->weightedAverage($stock->current_stock, $stock->average_cost, Decimal::fromMinor(abs($delta), 3), $unitCost);
                    $costSource = 'purchase';
                }
                $stock->current_stock = Decimal::fromMinor($after, 3);
                $stock->save();
                $this->syncItemCache($item);

                $transactionItem = $transaction->items()->create([
                    'inventory_item_id' => $item->id, 'inventory_stock_id' => $stock->id, 'unit_id' => $line['unit_id'] ?? $item->base_unit_id,
                    'quantity' => Decimal::fromMinor($delta, 3), 'before_quantity' => Decimal::fromMinor($before, 3), 'after_quantity' => Decimal::fromMinor($after, 3),
                    'unit_cost' => $unitCost, 'stock_value' => $this->costs->value(Decimal::fromMinor($delta, 3), $unitCost),
                    'cost_source' => $costSource, 'note' => $line['note'] ?? null,
                ]);
                $this->moveBatches($transactionItem, $item, $warehouseId, $delta, $line, $supplierId);
            }
            return $transaction->load('items.inventoryItem', 'warehouse', 'creator');
        }, 3);
    }

    public function reverse(InventoryTransaction $original, int $userId, string $reason): InventoryTransaction
    {
        return DB::transaction(function () use ($original, $userId, $reason) {
            $original = InventoryTransaction::query()->lockForUpdate()->findOrFail($original->id);
            if ($original->reversal()->exists()) {
                throw ValidationException::withMessages(['transaction' => 'Giao d?ch n?y ?? ???c ??o.']);
            }
            $reverseType = in_array($original->type, [InventoryTransactionType::In, InventoryTransactionType::AdjustmentIn, InventoryTransactionType::TransferIn, InventoryTransactionType::CustomerReturn], true)
                ? InventoryTransactionType::AdjustmentOut : InventoryTransactionType::AdjustmentIn;
            $lines = $original->items()->get()->map(fn ($line) => ['inventory_item_id' => $line->inventory_item_id, 'quantity' => abs((float) $line->quantity), 'note' => "??o {$original->transaction_code}"])->all();
            $reversal = $this->record($reverseType, $lines, $reason, userId: $userId, warehouseId: $original->warehouse_id, idempotencyKey: "reverse:{$original->id}", metadata: ['reversal_of' => $original->id]);
            $reversal->update(['reversal_of_id' => $original->id]);
            return $reversal;
        }, 3);
    }

    public function transfer(array $lines, int $fromWarehouseId, int $toWarehouseId, string $reason, int $userId, string $idempotencyKey): array
    {
        if ($fromWarehouseId === $toWarehouseId) {
            throw ValidationException::withMessages(['destination_warehouse_id' => 'Kho nh?n ph?i kh?c kho xu?t.']);
        }
        return DB::transaction(function () use ($lines, $fromWarehouseId, $toWarehouseId, $reason, $userId, $idempotencyKey) {
            $out = $this->record(InventoryTransactionType::TransferOut, $lines, $reason, userId: $userId, warehouseId: $fromWarehouseId, idempotencyKey: $idempotencyKey.':out', metadata: ['destination_warehouse_id' => $toWarehouseId]);
            $in = $this->record(InventoryTransactionType::TransferIn, $lines, $reason, userId: $userId, warehouseId: $toWarehouseId, idempotencyKey: $idempotencyKey.':in', metadata: ['source_warehouse_id' => $fromWarehouseId]);
            $out->update(['destination_warehouse_id' => $toWarehouseId]);
            return [$out, $in];
        }, 3);
    }

    public function deductForOrder(Order $order, ?int $userId = null): ?InventoryTransaction
    {
        $key = "order:{$order->id}:consume";
        if ($existing = InventoryTransaction::where('idempotency_key', $key)->first()) return $existing;
        $order->loadMissing('items.product.recipe.items.inventoryItem');
        $requirements = [];
        foreach ($order->items as $orderItem) {
            $recipe = $orderItem->product?->recipe;
            if (! $recipe || $recipe->status !== 'active') {
                InventoryOrderWarning::updateOrCreate(
                    ['order_id' => $order->id, 'product_id' => $orderItem->product_id, 'code' => 'missing_recipe'],
                    ['message' => "{$orderItem->product_name} ch?a c? c?ng th?c ho?t ??ng; kho kh?ng t? ??ng tr? m?n n?y.", 'context' => ['quantity' => $orderItem->quantity]]
                );
                continue;
            }
            $yield = Decimal::toMinor($recipe->yield_quantity, 3);
            foreach ($recipe->items as $recipeItem) {
                $base = $this->units->toBase($recipeItem->inventoryItem, $recipeItem->quantity, $recipeItem->unit_id);
                $wastage = 10000 + Decimal::toMinor($recipeItem->wastage_percent, 2);
                $required = (int) round(Decimal::toMinor($base, 3) * $orderItem->quantity * 1000 * $wastage / $yield / 10000);
                $requirements[$recipeItem->inventory_item_id] = ($requirements[$recipeItem->inventory_item_id] ?? 0) + $required;
            }
        }
        if ($requirements === []) return null;
        $lines = collect($requirements)->map(fn (int $quantity, int $itemId) => ['inventory_item_id' => $itemId, 'quantity' => Decimal::fromMinor($quantity, 3)])->values()->all();
        return $this->record(InventoryTransactionType::Out, $lines, "Tr? kho cho {$order->order_code}", userId: $userId, reference: $order, idempotencyKey: $key);
    }

    public function restoreForCancelledOrder(Order $order, int $userId, string $reason): InventoryTransaction
    {
        return $this->reverse(InventoryTransaction::where('idempotency_key', "order:{$order->id}:consume")->firstOrFail(), $userId, $reason);
    }

    private function syncItemCache(InventoryItem $item): void
    {
        $quantity = InventoryStock::where('inventory_item_id', $item->id)->sum('current_stock');
        $cost = InventoryStock::where('inventory_item_id', $item->id)->whereHas('warehouse', fn ($q) => $q->where('is_default', true))->value('average_cost') ?? $item->cost_price;
        $item->forceFill(['current_stock' => $quantity, 'cost_price' => $cost])->saveQuietly();
    }

    private function moveBatches($transactionItem, InventoryItem $item, int $warehouseId, int $delta, array $line, ?int $supplierId): void
    {
        if (! $item->track_batch || ! config('restaurant.inventory.enable_batch_tracking')) return;
        if ($delta > 0) {
            $batchCode = $line['batch_code'] ?? null;
            if (! $batchCode) throw ValidationException::withMessages(['batch_code' => "{$item->name} b?t bu?c c? m? l?."]);
            if ($item->track_expiry && config('restaurant.inventory.enable_expiry_tracking') && empty($line['expiry_date'])) {
                throw ValidationException::withMessages(['expiry_date' => "{$item->name} b?t bu?c c? h?n s? d?ng."]);
            }
            $batch = InventoryBatch::query()->lockForUpdate()->firstOrNew(['warehouse_id' => $warehouseId, 'inventory_item_id' => $item->id, 'batch_code' => $batchCode]);
            if ($batch->exists && abs((float) $batch->unit_cost - (float) $transactionItem->unit_cost) > 0.01) {
                throw ValidationException::withMessages(['batch_code' => 'Kh?ng th? nh?p th?m v?o l? c? v?i gi? kh?c.']);
            }
            $batch->fill(['supplier_id' => $supplierId, 'initial_quantity' => (float) ($batch->initial_quantity ?? 0) + $delta / 1000, 'remaining_quantity' => (float) ($batch->remaining_quantity ?? 0) + $delta / 1000, 'unit_cost' => $transactionItem->unit_cost, 'manufactured_date' => $line['manufactured_date'] ?? $batch->manufactured_date, 'expiry_date' => $line['expiry_date'] ?? $batch->expiry_date, 'status' => 'active'])->save();
            $transactionItem->batchMovements()->create(['inventory_batch_id' => $batch->id, 'quantity' => Decimal::fromMinor($delta, 3)]);
            return;
        }
        $remaining = abs($delta);
        $batches = InventoryBatch::query()->where('warehouse_id', $warehouseId)->where('inventory_item_id', $item->id)->where('remaining_quantity', '>', 0)->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date')->orderBy('created_at')->lockForUpdate()->get();
        foreach ($batches as $batch) {
            if ($remaining === 0) break;
            $available = Decimal::toMinor($batch->remaining_quantity, 3);
            $take = min($remaining, $available);
            $batch->update(['remaining_quantity' => Decimal::fromMinor($available - $take, 3), 'status' => $available === $take ? 'depleted' : 'active']);
            $transactionItem->batchMovements()->create(['inventory_batch_id' => $batch->id, 'quantity' => Decimal::fromMinor(-$take, 3)]);
            $remaining -= $take;
        }
        if ($remaining > 0) throw ValidationException::withMessages(['items' => "Kh?ng ?? t?n theo l? cho {$item->name}."]);
    }
}
