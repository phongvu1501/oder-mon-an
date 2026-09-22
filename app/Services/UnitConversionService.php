<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\UnitConversion;
use App\Support\Decimal;
use Illuminate\Validation\ValidationException;

class UnitConversionService
{
    public function toBase(InventoryItem $item, string|int|float $quantity, ?int $unitId): string
    {
        $item->loadMissing('baseUnit');
        $unitId ??= $item->base_unit_id;
        if ((int) $unitId === (int) $item->base_unit_id) {
            return Decimal::fromMinor(Decimal::toMinor($quantity, 3), 3);
        }

        $conversion = UnitConversion::query()
            ->where('from_unit_id', $unitId)->where('to_unit_id', $item->base_unit_id)
            ->where(fn($query) => $query->where('inventory_item_id', $item->id)->orWhereNull('inventory_item_id'))
            ->orderByRaw('inventory_item_id IS NULL')->first();
        if (! $conversion) {
            throw ValidationException::withMessages(['unit_id' => "Kh?ng c? quy ??i ??n v? h?p l? cho {$item->name}."]);
        }

        $source = Decimal::toMinor($quantity, 3);
        $multiplier = Decimal::toMinor($conversion->multiplier, 6);
        return Decimal::fromMinor((int) round($source * $multiplier / 1000000), 3);
    }
}
