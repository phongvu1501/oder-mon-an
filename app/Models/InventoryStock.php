<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStock extends Model
{
    protected $fillable = ['warehouse_id', 'inventory_item_id', 'current_stock', 'average_cost'];
    protected function casts(): array
    {
        return ['current_stock' => 'decimal:3', 'average_cost' => 'decimal:2'];
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
    public function transactionItems(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }
}
