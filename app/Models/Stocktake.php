<?php

namespace App\Models;

use App\Enums\StocktakeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stocktake extends Model
{
    protected $fillable = ['stocktake_code', 'warehouse_id', 'status', 'counted_at', 'note', 'created_by', 'reviewed_by', 'reviewed_at', 'confirmed_by', 'confirmed_at'];
    protected function casts(): array
    {
        return ['status' => StocktakeStatus::class, 'counted_at' => 'datetime', 'reviewed_at' => 'datetime', 'confirmed_at' => 'datetime'];
    }
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(StocktakeItem::class);
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
