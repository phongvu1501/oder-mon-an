<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return ['category_id' => Category::factory(), 'sku' => fake()->unique()->bothify('SP-####'), 'name' => ucfirst($name), 'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999), 'price' => fake()->numberBetween(10, 100) * 1000, 'cost_price' => 0, 'unit' => 'phần', 'status' => 'active', 'is_available' => true];
    }
}
