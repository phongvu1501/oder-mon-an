<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class RecipeService
{
    public function save(Product $product, array $data): Recipe
    {
        return DB::transaction(function () use ($product, $data) {
            $recipe = $product->recipe()->first();
            $version = $recipe ? $recipe->version + 1 : 1;
            $recipe = $product->recipe()->updateOrCreate([], [
                'yield_quantity' => $data['yield_quantity'], 'yield_unit_id' => $data['yield_unit_id'],
                'version' => $version, 'status' => $data['status'], 'note' => $data['note'] ?? null,
            ]);
            $inventoryItems = InventoryItem::whereIn('id', collect($data['items'])->pluck('inventory_item_id'))->get()->keyBy('id');
            $units = Unit::whereIn('id', collect($data['items'])->pluck('unit_id'))->get()->keyBy('id');
            $recipe->items()->delete();
            $recipe->items()->createMany(collect($data['items'])->map(function ($line) use ($units) {
                $unit = $units->get((int) $line['unit_id']);
                return ['inventory_item_id' => $line['inventory_item_id'], 'quantity' => $line['quantity'], 'unit_id' => $unit->id, 'unit' => $unit->symbol, 'wastage_percent' => $line['wastage_percent'] ?? 0];
            })->all());
            return $recipe->load('items.inventoryItem', 'items.unitDefinition', 'yieldUnit');
        }, 3);
    }
}
