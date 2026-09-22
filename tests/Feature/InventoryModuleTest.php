<?php

namespace Tests\Feature;

use App\Enums\InventoryDocumentStatus;
use App\Enums\InventoryDocumentType;
use App\Enums\InventoryTransactionType;
use App\Enums\OrderStatus;
use App\Models\InventoryItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryDocumentService;
use App\Services\InventoryMovementService;
use App\Services\JwtService;
use App\Services\OrderService;
use App\Services\StocktakeService;
use App\Services\TableSessionService;
use App\Services\UnitConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InventoryModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_is_draft_until_confirmed_and_uses_weighted_average_cost(): void
    {
        $item = $this->item(); $user = User::factory()->create();
        $first = $this->document(InventoryDocumentType::Receipt, $item, 10, 100, $user);
        $this->assertSame(InventoryDocumentStatus::Draft, $first->status);
        $this->assertEquals('0.000', $item->refresh()->current_stock);
        app(InventoryDocumentService::class)->confirm($first, $user->id);
        $second = $this->document(InventoryDocumentType::Receipt, $item, 10, 200, $user);
        app(InventoryDocumentService::class)->confirm($second, $user->id);
        $this->assertEquals('20.000', $item->refresh()->current_stock);
        $this->assertEquals('150.00', $item->cost_price);
        $this->assertDatabaseCount('inventory_transactions', 2);
    }

    public function test_document_cannot_be_confirmed_twice(): void
    {
        $user = User::factory()->create(); $document = $this->document(InventoryDocumentType::Receipt, $this->item(), 2, 100, $user);
        $service = app(InventoryDocumentService::class); $service->confirm($document, $user->id);
        $this->expectException(ValidationException::class);
        $service->confirm($document->refresh(), $user->id);
    }

    public function test_issue_cannot_exceed_available_stock(): void
    {
        $user = User::factory()->create(); $item = $this->item(); $this->receive($item, 3, 100, $user->id);
        $document = $this->document(InventoryDocumentType::Issue, $item, 4, null, $user);
        try {
            app(InventoryDocumentService::class)->confirm($document, $user->id);
            $this->fail('Expected insufficient stock.');
        } catch (ValidationException) {
            $this->assertEquals('3.000', $item->refresh()->current_stock);
            $this->assertSame(InventoryDocumentStatus::Draft, $document->refresh()->status);
        }
    }

    public function test_adjustment_in_out_and_waste_create_ledger(): void
    {
        $user = User::factory()->create(); $item = $this->item(); $this->receive($item, 10, 100, $user->id);
        foreach ([[InventoryDocumentType::Adjustment, 2], [InventoryDocumentType::Adjustment, -3], [InventoryDocumentType::Waste, 1]] as [$type, $quantity]) {
            app(InventoryDocumentService::class)->confirm($this->document($type, $item, $quantity, null, $user), $user->id);
        }
        $this->assertEquals('8.000', $item->refresh()->current_stock);
        $this->assertDatabaseHas('inventory_transactions', ['type' => 'adjustment_in']);
        $this->assertDatabaseHas('inventory_transactions', ['type' => 'adjustment_out']);
        $this->assertDatabaseHas('inventory_transactions', ['type' => 'waste']);
    }

    public function test_stocktake_confirmation_records_difference_once(): void
    {
        $user = User::factory()->create(); $item = $this->item(); $this->receive($item, 10, 100, $user->id);
        $service = app(StocktakeService::class);
        $stocktake = $service->create($this->warehouse()->id, [$item->id], now()->toDateTimeString(), null, $user->id);
        $service->start($stocktake); $service->recordCounts($stocktake->refresh(), [['inventory_item_id' => $item->id, 'physical_quantity' => 8, 'reason' => 'Counted']]);
        $service->review($stocktake->refresh(), $user->id); $service->confirm($stocktake->refresh(), $user->id);
        $this->assertEquals('8.000', $item->refresh()->current_stock);
        $this->assertDatabaseHas('inventory_transactions', ['type' => 'stocktake_adjustment', 'idempotency_key' => "stocktake:{$stocktake->id}:confirm"]);
    }

    public function test_batch_receipt_requires_expiry_and_issue_uses_fefo(): void
    {
        $user = User::factory()->create(); $item = $this->item(['track_batch' => true, 'track_expiry' => true]);
        $this->receive($item, 2, 100, $user->id, ['batch_code' => 'LATE', 'expiry_date' => now()->addDays(20)->toDateString()]);
        $this->receive($item, 2, 100, $user->id, ['batch_code' => 'EARLY', 'expiry_date' => now()->addDays(5)->toDateString()]);
        app(InventoryMovementService::class)->record(InventoryTransactionType::Out, [['inventory_item_id' => $item->id, 'quantity' => 3]], userId: $user->id);
        $this->assertDatabaseHas('inventory_batches', ['batch_code' => 'EARLY', 'remaining_quantity' => 0]);
        $this->assertDatabaseHas('inventory_batches', ['batch_code' => 'LATE', 'remaining_quantity' => 1]);
    }

    public function test_tracked_batch_without_expiry_rolls_back(): void
    {
        $item = $this->item(['track_batch' => true, 'track_expiry' => true]);
        $this->expectException(ValidationException::class);
        $this->receive($item, 2, 100, null, ['batch_code' => 'NO-EXPIRY']);
    }

    public function test_multi_line_failure_rolls_back_stock_and_ledger(): void
    {
        $first = $this->item(['sku' => 'A']); $second = $this->item(['sku' => 'B']);
        $this->receive($first, 1, 100); $this->receive($second, 1, 100);
        $count = \App\Models\InventoryTransaction::count();
        try {
            app(InventoryMovementService::class)->record(InventoryTransactionType::Out, [['inventory_item_id' => $first->id, 'quantity' => 1], ['inventory_item_id' => $second->id, 'quantity' => 2]]);
            $this->fail('Expected rollback.');
        } catch (ValidationException) {
            $this->assertEquals('1.000', $first->refresh()->current_stock);
            $this->assertEquals('1.000', $second->refresh()->current_stock);
            $this->assertSame($count, \App\Models\InventoryTransaction::count());
        }
    }

    public function test_order_consumption_is_idempotent_and_prepared_cancel_does_not_auto_restore(): void
    {
        $item = $this->item(); $this->receive($item, 10, 100);
        $product = Product::factory()->create();
        $recipe = Recipe::create(['product_id' => $product->id, 'yield_quantity' => 1, 'status' => 'active']);
        $recipe->items()->create(['inventory_item_id' => $item->id, 'quantity' => 1, 'unit' => 'kg', 'unit_id' => $item->base_unit_id, 'wastage_percent' => 0]);
        $session = app(TableSessionService::class)->open(RestaurantTable::factory()->create()); $user = User::factory()->create();
        $orders = app(OrderService::class); $order = $orders->create($session, [['product_id' => $product->id, 'quantity' => 2]], (string) Str::uuid());
        $orders->transition($order, OrderStatus::Confirmed, $user->id); $orders->transition($order->refresh(), OrderStatus::Preparing, $user->id);
        app(InventoryMovementService::class)->deductForOrder($order->refresh(), $user->id);
        $this->assertEquals('8.000', $item->refresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 2);
        $orders->transition($order->refresh(), OrderStatus::Cancelled, $user->id, 'Kh?ch h?y sau khi b?p l?m');
        $this->assertEquals('8.000', $item->refresh()->current_stock);
    }

    public function test_missing_recipe_creates_warning_without_false_deduction_marker(): void
    {
        $session = app(TableSessionService::class)->open(RestaurantTable::factory()->create()); $user = User::factory()->create();
        $orders = app(OrderService::class); $order = $orders->create($session, [['product_id' => Product::factory()->create()->id, 'quantity' => 1]], (string) Str::uuid());
        $orders->transition($order, OrderStatus::Confirmed, $user->id); $orders->transition($order->refresh(), OrderStatus::Preparing, $user->id);
        $this->assertNull($order->refresh()->inventory_deducted_at);
        $this->assertDatabaseHas('inventory_order_warnings', ['order_id' => $order->id, 'code' => 'missing_recipe']);
    }

    public function test_soft_deleted_item_remains_visible_from_ledger(): void
    {
        $item = $this->item(); $transaction = $this->receive($item, 1, 100); $item->delete();
        $this->assertTrue($transaction->items()->first()->inventoryItem->trashed());
    }

    public function test_incompatible_unit_without_item_conversion_is_rejected(): void
    {
        $item = $this->item(); $bottle = Unit::where('code', 'chai')->firstOrFail();
        $this->expectException(ValidationException::class);
        app(UnitConversionService::class)->toBase($item, 1, $bottle->id);
    }

    public function test_confirm_route_requires_confirm_permission(): void
    {
        $user = User::factory()->create(); $role = Role::create(['name' => 'Viewer', 'slug' => 'viewer', 'status' => 'active']);
        $permission = Permission::where('name', 'inventory.view')->firstOrFail(); $role->permissions()->attach($permission); $user->roles()->attach($role);
        $document = $this->document(InventoryDocumentType::Receipt, $this->item(), 1, 100, $user);
        $token = app(JwtService::class)->accessToken($user);
        $this->withHeader('Authorization', 'Bearer '.$token)->post(route('inventory.documents.confirm', $document))->assertForbidden();
        $this->assertSame(InventoryDocumentStatus::Draft, $document->refresh()->status);
    }

    public function test_supplier_soft_delete_keeps_transaction_reference(): void
    {
        $supplier = Supplier::create(['code' => 'NCC-T', 'name' => 'Test', 'status' => 'active']);
        $transaction = app(InventoryMovementService::class)->record(InventoryTransactionType::In, [['inventory_item_id' => $this->item()->id, 'quantity' => 1, 'unit_cost' => 100]], supplierId: $supplier->id);
        $supplier->delete();
        $this->assertDatabaseHas('inventory_transactions', ['id' => $transaction->id, 'supplier_id' => $supplier->id]);
    }

    private function item(array $attributes = []): InventoryItem
    {
        $unit = Unit::where('code', 'kg')->firstOrFail();
        return InventoryItem::factory()->create(array_merge(['base_unit_id' => $unit->id, 'unit' => 'kg', 'current_stock' => 0, 'cost_price' => 0], $attributes));
    }

    private function warehouse(): Warehouse { return Warehouse::where('is_default', true)->firstOrFail(); }

    private function receive(InventoryItem $item, float $quantity, float $cost, ?int $userId = null, array $extra = [])
    {
        return app(InventoryMovementService::class)->record(InventoryTransactionType::In, [array_merge(['inventory_item_id' => $item->id, 'quantity' => $quantity, 'unit_cost' => $cost], $extra)], userId: $userId);
    }

    private function document(InventoryDocumentType $type, InventoryItem $item, float $quantity, ?float $cost, User $user)
    {
        return app(InventoryDocumentService::class)->create($type, [
            'warehouse_id' => $this->warehouse()->id, 'transaction_date' => now()->toDateTimeString(), 'reason' => $type === InventoryDocumentType::Receipt ? null : 'Test',
            'items' => [['inventory_item_id' => $item->id, 'unit_id' => $item->base_unit_id, 'quantity' => $quantity, 'unit_cost' => $cost]],
        ], $user->id);
    }
}
