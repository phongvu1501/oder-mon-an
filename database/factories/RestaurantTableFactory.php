<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RestaurantTableFactory extends Factory
{
    public function definition(): array
    {
        $code = fake()->unique()->bothify('B-###');

        return ['code' => $code, 'name' => 'Bàn '.$code, 'capacity' => 4, 'location' => 'Tầng 1', 'status' => 'available', 'qr_token' => Str::random(64)];
    }
}
