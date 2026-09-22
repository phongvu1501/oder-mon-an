<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    protected $fillable = ['warehouse_id', 'inventory_item_id', 'supplier_id', 'source_document_item_id', 'batch_code', 'initial_quantity', 'remaining_quantity', 'unit_cost', 'manufactured_date', 'expiry_date', 'status'];
    protected function casts(): array
    {
        return ['initial_quantity' => 'decimal:3', 'remaining_quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'manufactured_date' => 'date', 'expiry_date' => 'date'];
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function sourceDocumentItem(): BelongsTo
    {
        return $this->belongsTo(InventoryDocumentItem::class, 'source_document_item_id');
    }
    public function movements(): HasMany
    {
        return $this->hasMany(InventoryBatchMovement::class);
    }
}
