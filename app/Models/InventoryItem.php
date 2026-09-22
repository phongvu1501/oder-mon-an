<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'sku', 'unit', 'base_unit_id', 'minimum_stock', 'status', 'track_batch', 'track_expiry'];

    protected function casts(): array
    {
        return ['status' => RecordStatus::class, 'current_stock' => 'decimal:3', 'minimum_stock' => 'decimal:3', 'cost_price' => 'decimal:2', 'track_batch' => 'boolean', 'track_expiry' => 'boolean'];
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }
    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }
    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }
    public function recipeItems(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }
    public function getStockStatusAttribute(): string
    {
        return (float) $this->current_stock <= 0 ? 'out' : ((float) $this->current_stock <= (float) $this->minimum_stock ? 'low' : 'ok');
    }
}
