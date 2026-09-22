<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;
    protected $fillable = ['code', 'name', 'address', 'is_default', 'status'];
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }
    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    public function documents(): HasMany
    {
        return $this->hasMany(InventoryDocument::class);
    }
    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }
    public function stocktakes(): HasMany
    {
        return $this->hasMany(Stocktake::class);
    }
}
