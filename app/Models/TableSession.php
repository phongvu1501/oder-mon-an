<?php

namespace App\Models;

use App\Enums\TableSessionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TableSession extends Model
{
    use HasFactory;

    protected $fillable = ['restaurant_table_id', 'session_code', 'status', 'guest_count', 'opened_at', 'closed_at'];

    protected function casts(): array
    {
        return ['status' => TableSessionStatus::class, 'opened_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function bill(): HasOne
    {
        return $this->hasOne(Bill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
