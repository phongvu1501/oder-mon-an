<?php

namespace Database\Factories;

use App\Models\TableSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return ['order_code' => 'ORD-'.Str::upper(Str::random(12)), 'table_session_id' => TableSession::factory(), 'idempotency_key' => (string) Str::uuid(), 'status' => 'pending', 'subtotal' => 50000, 'discount' => 0, 'tax' => 0, 'total' => 50000];
    }
}
