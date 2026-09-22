<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ['code', 'name', 'symbol', 'category', 'decimal_places', 'status'];
    protected function casts(): array
    {
        return ['decimal_places' => 'integer'];
    }
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'base_unit_id');
    }
    public function conversionsFrom(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'from_unit_id');
    }
    public function conversionsTo(): HasMany
    {
        return $this->hasMany(UnitConversion::class, 'to_unit_id');
    }
}
