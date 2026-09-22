<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StocktakeItem extends Model
{
    protected $fillable = ['stocktake_id', 'inventory_item_id', 'system_quantity', 'physical_quantity', 'difference_quantity', 'unit_cost', 'difference_value', 'reason'];
    protected function casts(): array
    {
        return ['system_quantity' => 'decimal:3', 'physical_quantity' => 'decimal:3', 'difference_quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'difference_value' => 'decimal:2'];
    }
    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class);
    }
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
}
