<?php

namespace App\Models;

use App\Enums\InventoryTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryTransaction extends Model
{
    protected $fillable = ['transaction_code', 'type', 'reference_type', 'reference_id', 'supplier_id', 'reason', 'created_by'];

    protected function casts(): array
    {
        return ['type' => InventoryTransactionType::class];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
