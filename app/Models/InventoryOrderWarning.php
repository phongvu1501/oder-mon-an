<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryOrderWarning extends Model
{
    protected $fillable = ['order_id', 'product_id', 'code', 'message', 'context', 'resolved_at', 'resolved_by'];
    protected function casts(): array
    {
        return ['context' => 'array', 'resolved_at' => 'datetime'];
    }
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
