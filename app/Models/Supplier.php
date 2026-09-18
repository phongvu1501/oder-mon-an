<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'contact_name', 'phone', 'email', 'address', 'status', 'note'];

    protected function casts(): array
    {
        return ['status' => RecordStatus::class];
    }
}
