<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'sku', 'unit', 'current_stock', 'minimum_stock', 'cost_price', 'status'];

    protected function casts(): array
    {
        return ['status' => RecordStatus::class, 'current_stock' => 'decimal:3', 'minimum_stock' => 'decimal:3', 'cost_price' => 'decimal:2'];
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }
}
