<?php

namespace Database\Factories;

use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TableSessionFactory extends Factory
{
    public function definition(): array
    {
        return ['restaurant_table_id' => RestaurantTable::factory(), 'session_code' => 'SES-'.Str::upper(Str::random(12)), 'status' => 'active', 'guest_count' => 2, 'opened_at' => now()];
    }
}
