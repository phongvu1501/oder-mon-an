<?php

namespace App\Services;

use App\Enums\InventoryTransactionType;
use App\Enums\StocktakeStatus;
use App\Models\InventoryItem;
use App\Models\InventoryStock;
use App\Models\Stocktake;
use App\Support\BusinessCode;
use App\Support\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StocktakeService
{
    public function __construct(private readonly InventoryMovementService $inventory) {}

    public function create(int $warehouseId, array $itemIds, string $countedAt, ?string $note, int $userId): Stocktake
    {
        return DB::transaction(function () use ($warehouseId, $itemIds, $countedAt, $note, $userId) {
            $items = InventoryItem::query()->whereIn('id', $itemIds)->where('status', 'active')->get();
            if ($items->count() !== collect($itemIds)->unique()->count()) throw ValidationException::withMessages(['items' => 'Danh s?ch ki?m k? kh?ng h?p l?.']);
            $stocks = InventoryStock::where('warehouse_id', $warehouseId)->whereIn('inventory_item_id', $itemIds)->get()->keyBy('inventory_item_id');
            $stocktake = Stocktake::create(['stocktake_code' => BusinessCode::make('KK'), 'warehouse_id' => $warehouseId, 'status' => StocktakeStatus::Draft, 'counted_at' => $countedAt, 'note' => $note, 'created_by' => $userId]);
            $stocktake->items()->createMany($items->map(function ($item) use ($stocks) {
                $stock = $stocks->get($item->id);
                return ['inventory_item_id' => $item->id, 'system_quantity' => $stock?->current_stock ?? 0, 'unit_cost' => $stock?->average_cost ?? $item->cost_price];
            })->all());
            return $stocktake->load('items.inventoryItem', 'warehouse');
        });
    }

    public function start(Stocktake $stocktake): Stocktake
    {
        return $this->transition($stocktake, StocktakeStatus::Counting);
    }

    public function recordCounts(Stocktake $stocktake, array $lines): Stocktake
    {
        return DB::transaction(function () use ($stocktake, $lines) {
            $stocktake = Stocktake::query()->lockForUpdate()->findOrFail($stocktake->id);
            if (! in_array($stocktake->status, [StocktakeStatus::Draft, StocktakeStatus::Counting], true)) {
                throw ValidationException::withMessages(['status' => 'Kh?ng th? nh?p s? ??m ? tr?ng th?i hi?n t?i.']);
            }
            $existing = $stocktake->items()->get()->keyBy('inventory_item_id');
            foreach ($lines as $index => $line) {
                $item = $existing->get((int) $line['inventory_item_id']);
                if (! $item) throw ValidationException::withMessages(["items.{$index}" => 'M?t h?ng kh?ng thu?c phi?u ki?m k?.']);
                $physical = Decimal::toMinor($line['physical_quantity'], 3);
                if ($physical < 0) throw ValidationException::withMessages(["items.{$index}.physical_quantity" => 'T?n th?c t? kh?ng ???c ?m.']);
                $system = Decimal::toMinor($item->system_quantity, 3);
                $difference = $physical - $system;
                $item->update(['physical_quantity' => Decimal::fromMinor($physical, 3), 'difference_quantity' => Decimal::fromMinor($difference, 3), 'difference_value' => Decimal::fromMinor((int) round($difference * Decimal::toMinor($item->unit_cost, 2) / 1000), 2), 'reason' => $line['reason'] ?? null]);
            }
            return $stocktake->refresh()->load('items.inventoryItem');
        });
    }

    public function review(Stocktake $stocktake, int $userId): Stocktake
    {
        if ($stocktake->items()->whereNull('physical_quantity')->exists()) throw ValidationException::withMessages(['items' => 'Ph?i nh?p ?? s? ??m th?c t? tr??c khi duy?t.']);
        $updated = $this->transition($stocktake, StocktakeStatus::Review);
        $updated->update(['reviewed_by' => $userId, 'reviewed_at' => now()]);
        return $updated->refresh();
    }

    public function confirm(Stocktake $stocktake, int $userId): Stocktake
    {
        return DB::transaction(function () use ($stocktake, $userId) {
            $stocktake = Stocktake::query()->lockForUpdate()->findOrFail($stocktake->id);
            if ($stocktake->status !== StocktakeStatus::Review) throw ValidationException::withMessages(['status' => 'Phi?u ph?i ? tr?ng th?i ch? x?c nh?n.']);
            $stocktake->load('items.inventoryItem');
            $stocks = InventoryStock::where('warehouse_id', $stocktake->warehouse_id)->whereIn('inventory_item_id', $stocktake->items->pluck('inventory_item_id'))->lockForUpdate()->get()->keyBy('inventory_item_id');
            $lines = [];
            foreach ($stocktake->items as $line) {
                $stock = $stocks->get($line->inventory_item_id);
                $system = Decimal::toMinor($stock?->current_stock ?? 0, 3);
                $physical = Decimal::toMinor($line->physical_quantity, 3);
                $difference = $physical - $system;
                $line->update(['system_quantity' => Decimal::fromMinor($system, 3), 'difference_quantity' => Decimal::fromMinor($difference, 3), 'unit_cost' => $stock?->average_cost ?? $line->unit_cost, 'difference_value' => Decimal::fromMinor((int) round($difference * Decimal::toMinor($stock?->average_cost ?? $line->unit_cost, 2) / 1000), 2)]);
                if ($difference !== 0) $lines[] = ['inventory_item_id' => $line->inventory_item_id, 'quantity' => Decimal::fromMinor($difference, 3), 'note' => $line->reason];
            }
            if ($lines !== []) $this->inventory->record(InventoryTransactionType::StocktakeAdjustment, $lines, "Ki?m k? {$stocktake->stocktake_code}", userId: $userId, reference: $stocktake, warehouseId: $stocktake->warehouse_id, idempotencyKey: "stocktake:{$stocktake->id}:confirm");
            $stocktake->update(['status' => StocktakeStatus::Confirmed, 'confirmed_by' => $userId, 'confirmed_at' => now()]);
            return $stocktake->refresh()->load('items.inventoryItem', 'warehouse');
        }, 3);
    }

    private function transition(Stocktake $stocktake, StocktakeStatus $next): Stocktake
    {
        $stocktake->refresh();
        if (! $stocktake->status->canTransitionTo($next)) throw ValidationException::withMessages(['status' => 'Chuy?n tr?ng th?i ki?m k? kh?ng h?p l?.']);
        $stocktake->update(['status' => $next]);
        return $stocktake->refresh();
    }
}
