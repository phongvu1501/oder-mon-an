<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return ['order_id' => Order::factory(), 'product_id' => Product::factory(), 'product_name' => 'Món kiểm thử', 'unit_price' => 50000, 'quantity' => 1, 'subtotal' => 50000, 'status' => 'pending'];
    }
}
