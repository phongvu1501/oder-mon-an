<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $constraints = [
        'users' => ["chk_users_status CHECK (status IN ('active','inactive'))"],
        'roles' => ["chk_roles_status CHECK (status IN ('active','inactive'))"],
        'categories' => ["chk_categories_status CHECK (status IN ('active','inactive'))"],
        'products' => [
            "chk_products_status CHECK (status IN ('active','inactive'))",
            'chk_products_money CHECK (price >= 0 AND cost_price >= 0)',
        ],
        'restaurant_tables' => [
            "chk_tables_status CHECK (status IN ('available','occupied','reserved','maintenance','inactive'))",
            'chk_tables_capacity CHECK (capacity > 0)',
        ],
        'table_sessions' => [
            "chk_sessions_status CHECK (status IN ('active','closed','cancelled'))",
            'chk_sessions_guests CHECK (guest_count > 0)',
        ],
        'orders' => [
            "chk_orders_status CHECK (status IN ('pending','confirmed','preparing','ready','served','completed','cancelled'))",
            'chk_orders_money CHECK (subtotal >= 0 AND discount >= 0 AND tax >= 0 AND total >= 0)',
        ],
        'order_items' => [
            "chk_order_items_status CHECK (status IN ('pending','preparing','ready','served','cancelled'))",
            'chk_order_items_values CHECK (unit_price >= 0 AND quantity > 0 AND subtotal >= 0)',
        ],
        'bills' => [
            "chk_bills_status CHECK (status IN ('unpaid','partial','paid','cancelled'))",
            'chk_bills_money CHECK (subtotal >= 0 AND discount >= 0 AND tax >= 0 AND total >= 0 AND paid_amount >= 0 AND paid_amount <= total)',
        ],
        'payments' => [
            "chk_payments_method CHECK (method IN ('cash','bank_transfer','qr'))",
            "chk_payments_status CHECK (status IN ('pending','paid','failed','cancelled','refunded'))",
            'chk_payments_amount CHECK (amount > 0)',
        ],
        'suppliers' => ["chk_suppliers_status CHECK (status IN ('active','inactive'))"],
        'inventory_items' => [
            "chk_inventory_items_status CHECK (status IN ('active','inactive'))",
            'chk_inventory_items_values CHECK (current_stock >= 0 AND minimum_stock >= 0 AND cost_price >= 0)',
        ],
        'recipes' => ['chk_recipes_yield CHECK (yield_quantity > 0)'],
        'recipe_items' => ['chk_recipe_items_quantity CHECK (quantity > 0)'],
        'inventory_transactions' => ["chk_inventory_transactions_type CHECK (type IN ('in','out','adjustment','waste','return'))"],
        'inventory_transaction_items' => ['chk_inventory_transaction_values CHECK (quantity <> 0 AND before_quantity >= 0 AND after_quantity >= 0 AND (unit_cost IS NULL OR unit_cost >= 0))'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->constraints as $table => $constraints) {
            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$constraint}");
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->constraints as $table => $constraints) {
            foreach ($constraints as $constraint) {
                $name = strtok($constraint, ' ');
                DB::statement("ALTER TABLE {$table} DROP CHECK {$name}");
            }
        }
    }
};
