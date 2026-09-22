<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryTransactionItem extends Model
{
    protected $fillable = ['inventory_transaction_id', 'inventory_item_id', 'inventory_stock_id', 'unit_id', 'quantity', 'before_quantity', 'after_quantity', 'unit_cost', 'stock_value', 'cost_source', 'cost_review_required', 'note'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'before_quantity' => 'decimal:3', 'after_quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'stock_value' => 'decimal:2', 'cost_review_required' => 'boolean'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class)->withTrashed();
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(InventoryStock::class, 'inventory_stock_id');
    }
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
    public function batchMovements(): HasMany
    {
        return $this->hasMany(InventoryBatchMovement::class);
    }
}
