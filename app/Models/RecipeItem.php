<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeItem extends Model
{
    protected $fillable = ['recipe_id', 'inventory_item_id', 'quantity', 'unit', 'unit_id', 'wastage_percent'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'wastage_percent' => 'decimal:2'];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    public function unitDefinition(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
