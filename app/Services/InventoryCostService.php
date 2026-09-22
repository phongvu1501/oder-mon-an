<?php

namespace App\Services;

use App\Support\Decimal;
use InvalidArgumentException;

class InventoryCostService
{
    public function weightedAverage(string|int|float $currentQuantity, string|int|float $currentCost, string|int|float $receivedQuantity, string|int|float $receivedCost): string
    {
        if (config('restaurant.inventory.cost_method') !== 'weighted_average') {
            throw new InvalidArgumentException('Unsupported inventory costing method.');
        }
        $currentQty = Decimal::toMinor($currentQuantity, 3);
        $receivedQty = Decimal::toMinor($receivedQuantity, 3);
        if ($receivedQty <= 0) {
            throw new InvalidArgumentException('Received quantity must be greater than zero.');
        }
        $currentCostMinor = Decimal::toMinor($currentCost, 2);
        $receivedCostMinor = Decimal::toMinor($receivedCost, 2);
        $totalQty = $currentQty + $receivedQty;
        $averageMinor = (int) round((($currentQty * $currentCostMinor) + ($receivedQty * $receivedCostMinor)) / $totalQty);
        return Decimal::fromMinor($averageMinor, 2);
    }

    public function value(string|int|float $quantity, string|int|float $cost): string
    {
        $valueMinor = (int) round(Decimal::toMinor($quantity, 3) * Decimal::toMinor($cost, 2) / 1000);
        return Decimal::fromMinor(abs($valueMinor), 2);
    }
}
