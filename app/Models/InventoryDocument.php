<?php

namespace App\Models;

use App\Enums\InventoryDocumentStatus;
use App\Enums\InventoryDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryDocument extends Model
{
    protected $fillable = ['document_code', 'type', 'status', 'warehouse_id', 'destination_warehouse_id', 'supplier_id', 'transaction_date', 'reason', 'recipient', 'note', 'total_amount', 'idempotency_key', 'created_by', 'confirmed_by', 'confirmed_at', 'cancelled_by', 'cancelled_at', 'cancel_reason'];
    protected function casts(): array
    {
        return ['type' => InventoryDocumentType::class, 'status' => InventoryDocumentStatus::class, 'transaction_date' => 'datetime', 'total_amount' => 'decimal:2', 'confirmed_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }
    public function items(): HasMany
    {
        return $this->hasMany(InventoryDocumentItem::class);
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'reference_id')->where('reference_type', $this->getMorphClass());
    }
}
