<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'status'];

    protected function casts(): array
    {
        return ['status' => RecordStatus::class];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
