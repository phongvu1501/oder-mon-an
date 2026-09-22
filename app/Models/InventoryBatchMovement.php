<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBatchMovement extends Model
{
    protected $fillable = ['inventory_transaction_item_id', 'inventory_batch_id', 'quantity'];
    protected function casts(): array
    {
        return ['quantity' => 'decimal:3'];
    }
    public function transactionItem(): BelongsTo
    {
        return $this->belongsTo(InventoryTransactionItem::class, 'inventory_transaction_item_id');
    }
    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }
}
