<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\TableStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\OrderService;
use App\Services\TableSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_same_table_returns_one_active_session(): void
    {
        $table = RestaurantTable::factory()->create();
        $service = app(TableSessionService::class);

        $first = $service->open($table, 2);
        $second = $service->open($table, 4);

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('table_sessions', 1);
        $this->assertSame(TableStatus::Occupied, $table->refresh()->status);
        $this->assertDatabaseHas('bills', ['table_session_id' => $first->id, 'status' => 'unpaid']);
    }

    public function test_order_snapshots_price_and_reuses_idempotency_key(): void
    {
        $session = app(TableSessionService::class)->open(RestaurantTable::factory()->create());
        $product = Product::factory()->create(['price' => 40000]);
        $key = (string) Str::uuid();
        $service = app(OrderService::class);

        $first = $service->create($session, [['product_id' => $product->id, 'quantity' => 2]], $key);
        $product->update(['price' => 50000]);
        $retry = $service->create($session, [['product_id' => $product->id, 'quantity' => 2]], $key);

        $this->assertTrue($first->is($retry));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('order_items', ['order_id' => $first->id, 'unit_price' => 40000, 'subtotal' => 80000]);
        $this->assertDatabaseHas('bills', ['table_session_id' => $session->id, 'total' => 80000]);
    }

    public function test_unavailable_product_cannot_be_ordered(): void
    {
        $this->expectException(ValidationException::class);
        $session = app(TableSessionService::class)->open(RestaurantTable::factory()->create());
        $product = Product::factory()->create(['is_available' => false]);
        app(OrderService::class)->create($session, [['product_id' => $product->id, 'quantity' => 1]], (string) Str::uuid());
    }

    public function test_preparing_deducts_recipe_inventory_once(): void
    {
        $session = app(TableSessionService::class)->open(RestaurantTable::factory()->create());
        $product = Product::factory()->create(['price' => 45000]);
        $ingredient = InventoryItem::factory()->create(['current_stock' => 10]);
        $recipe = Recipe::create(['product_id' => $product->id, 'yield_quantity' => 1]);
        $recipe->items()->create(['inventory_item_id' => $ingredient->id, 'quantity' => 0.2, 'unit' => 'kg']);
        $user = User::factory()->create();
        $service = app(OrderService::class);
        $order = $service->create($session, [['product_id' => $product->id, 'quantity' => 2]], (string) Str::uuid());

        $service->transition($order, OrderStatus::Confirmed, $user->id);
        $service->transition($order->refresh(), OrderStatus::Preparing, $user->id);

        $this->assertEquals('9.600', $ingredient->refresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 1);
        $this->assertNotNull($order->refresh()->inventory_deducted_at);
    }
}
