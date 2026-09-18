<?php

namespace Tests\Feature;

use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\TableSessionStatus;
use App\Enums\TableStatus;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TableSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_negative_inventory_is_prevented_and_transaction_rolls_back(): void
    {
        $item = InventoryItem::factory()->create(['current_stock' => 1]);

        try {
            app(InventoryService::class)->record(InventoryTransactionType::Out, [['inventory_item_id' => $item->id, 'quantity' => 2]]);
            $this->fail('Expected stock validation failure.');
        } catch (ValidationException) {
            $this->assertEquals('1.000', $item->refresh()->current_stock);
            $this->assertDatabaseCount('inventory_transactions', 0);
            $this->assertDatabaseCount('inventory_transaction_items', 0);
        }
    }

    public function test_paid_bill_closes_session_and_releases_table(): void
    {
        $table = RestaurantTable::factory()->create();
        $session = app(TableSessionService::class)->open($table);
        $user = User::factory()->create();
        $orders = app(OrderService::class);
        $order = $orders->create($session, [['product_id' => Product::factory()->create(['price' => 50000])->id, 'quantity' => 1]], (string) Str::uuid());
        foreach ([OrderStatus::Confirmed, OrderStatus::Preparing, OrderStatus::Ready, OrderStatus::Served] as $status) {
            $order = $orders->transition($order, $status, $user->id);
        }

        $payments = app(PaymentService::class);
        $payment = $payments->create($session->bill, PaymentMethod::Cash, '50000', (string) Str::uuid());
        $payments->confirm($payment, $user->id, 'CASH-TEST');

        $this->assertSame(TableSessionStatus::Closed, $session->refresh()->status);
        $this->assertSame(TableStatus::Available, $table->refresh()->status);
        $this->assertDatabaseHas('bills', ['table_session_id' => $session->id, 'status' => 'paid', 'paid_amount' => 50000]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_payment_cannot_exceed_bill_balance(): void
    {
        $this->expectException(ValidationException::class);
        $session = app(TableSessionService::class)->open(RestaurantTable::factory()->create());
        app(OrderService::class)->create($session, [['product_id' => Product::factory()->create(['price' => 50000])->id, 'quantity' => 1]], (string) Str::uuid());
        app(PaymentService::class)->create($session->bill->refresh(), PaymentMethod::Cash, '50001', (string) Str::uuid());
    }
}
