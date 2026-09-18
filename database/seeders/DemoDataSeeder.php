<?php

namespace Database\Seeders;

use App\Enums\InventoryTransactionType;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Super Admin', 'superadmin@odermonan.test', 'super-admin'], ['Admin Demo', 'admin@odermonan.test', 'admin'],
            ['Manager Demo', 'manager@odermonan.test', 'manager'], ['Staff Demo', 'staff@odermonan.test', 'staff'],
            ['Kitchen Demo', 'kitchen@odermonan.test', 'kitchen'],
        ];
        foreach ($accounts as [$name, $email, $roleSlug]) {
            $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => Hash::make('Password@123'), 'status' => 'active']);
            $user->roles()->sync([Role::where('slug', $roleSlug)->value('id')]);
        }

        foreach (['Món chính', 'Đồ uống', 'Món phụ', 'Tráng miệng'] as $index => $name) {
            Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'status' => 'active', 'sort_order' => $index + 1]);
        }
        $products = [
            ['Món chính', 'Cơm gà xối mỡ', 45000], ['Món chính', 'Cơm sườn nướng', 52000], ['Món chính', 'Mì xào bò', 55000],
            ['Món chính', 'Bún bò Huế', 50000], ['Món chính', 'Cơm chiên hải sản', 60000], ['Món phụ', 'Khoai tây chiên', 32000],
            ['Món phụ', 'Gỏi cuốn', 35000], ['Món phụ', 'Salad rau', 38000], ['Đồ uống', 'Coca Cola', 18000],
            ['Đồ uống', 'Pepsi', 18000], ['Đồ uống', 'Trà đào', 28000], ['Đồ uống', 'Nước suối', 12000],
            ['Tráng miệng', 'Flan caramel', 22000], ['Tráng miệng', 'Chè khúc bạch', 30000],
            ['Tráng miệng', 'Trái cây theo mùa', 35000], ['Đồ uống', 'Cà phê sữa', 25000],
        ];
        foreach ($products as $index => [$category, $name, $price]) {
            Product::updateOrCreate(['slug' => Str::slug($name)], [
                'category_id' => Category::where('name', $category)->value('id'), 'sku' => 'SP'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'name' => $name, 'description' => 'Món được chuẩn bị trong ngày.', 'price' => $price,
                'cost_price' => (int) ($price * 0.45), 'unit' => 'phần', 'status' => 'active', 'is_available' => true, 'sort_order' => $index + 1,
            ]);
        }
        foreach (range(1, 10) as $number) {
            $code = 'B'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            RestaurantTable::updateOrCreate(['code' => $code], [
                'name' => 'Bàn '.str_pad((string) $number, 2, '0', STR_PAD_LEFT), 'capacity' => $number > 6 ? 6 : 4,
                'location' => $number > 6 ? 'Tầng 2' : 'Tầng 1', 'status' => 'available', 'qr_token' => Str::random(64),
            ]);
        }

        $supplier = Supplier::updateOrCreate(['name' => 'Nhà cung cấp Demo'], ['contact_name' => 'Liên hệ Demo', 'phone' => '0900000000', 'status' => 'active']);
        $stock = [
            ['GA', 'Thịt gà', 'kg', 20, 5, 85000], ['GAO', 'Gạo', 'kg', 50, 10, 18000], ['RAU', 'Rau xanh', 'kg', 15, 3, 25000],
            ['DAU', 'Dầu ăn', 'lít', 12, 3, 42000], ['COCA', 'Coca Cola', 'chai', 100, 20, 9500], ['PEPSI', 'Pepsi', 'chai', 100, 20, 9500],
        ];
        $opening = [];
        foreach ($stock as [$sku, $name, $unit, $quantity, $minimum, $cost]) {
            $item = InventoryItem::updateOrCreate(['sku' => $sku], ['name' => $name, 'unit' => $unit, 'minimum_stock' => $minimum, 'cost_price' => $cost, 'status' => 'active']);
            $opening[] = ['inventory_item_id' => $item->id, 'quantity' => $quantity, 'unit_cost' => $cost];
        }
        if (! InventoryTransaction::where('reason', 'Số dư đầu kỳ demo')->exists()) {
            app(InventoryService::class)->record(InventoryTransactionType::In, $opening, 'Số dư đầu kỳ demo', $supplier->id, User::where('email', 'superadmin@odermonan.test')->value('id'));
        }

        $recipe = Recipe::updateOrCreate(['product_id' => Product::where('slug', Str::slug('Cơm gà xối mỡ'))->value('id')], ['yield_quantity' => 1]);
        $recipe->items()->updateOrCreate(['inventory_item_id' => InventoryItem::where('sku', 'GAO')->value('id')], ['quantity' => 0.2, 'unit' => 'kg']);
        $recipe->items()->updateOrCreate(['inventory_item_id' => InventoryItem::where('sku', 'GA')->value('id')], ['quantity' => 0.15, 'unit' => 'kg']);
    }
}
