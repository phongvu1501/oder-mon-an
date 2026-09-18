<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => ucfirst(fake()->unique()->words(2, true)), 'sku' => fake()->unique()->bothify('NL-####'), 'unit' => 'kg', 'current_stock' => 0, 'minimum_stock' => 5, 'cost_price' => 10000, 'status' => 'active'];
    }
}
