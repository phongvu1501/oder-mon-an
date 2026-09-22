<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'name', 'contact_name', 'phone', 'email', 'tax_code', 'address', 'status', 'note'];

    protected function casts(): array
    {
        return ['status' => RecordStatus::class];
    }

    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }
    public function inventoryDocuments(): HasMany
    {
        return $this->hasMany(InventoryDocument::class);
    }
}
