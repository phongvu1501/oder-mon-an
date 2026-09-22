<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryDocumentItem extends Model
{
    protected $fillable = ['inventory_document_id', 'inventory_item_id', 'unit_id', 'quantity', 'base_quantity', 'unit_cost', 'subtotal', 'batch_code', 'manufactured_date', 'expiry_date', 'reason', 'note'];
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3', 'base_quantity' => 'decimal:3', 'unit_cost' => 'decimal:2', 'subtotal' => 'decimal:2', 'manufactured_date' => 'date', 'expiry_date' => 'date'];
    }
    public function document(): BelongsTo
    {
        return $this->belongsTo(InventoryDocument::class, 'inventory_document_id');
    }
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
