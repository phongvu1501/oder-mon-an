<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->string('symbol', 30);
            $table->string('category', 30)->index();
            $table->unsignedTinyInteger('decimal_places')->default(3);
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('code', 30)->nullable()->unique()->after('id');
            $table->string('tax_code', 50)->nullable()->index()->after('email');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->foreignId('base_unit_id')->nullable()->after('unit')->constrained('units')->restrictOnDelete();
            $table->boolean('track_batch')->default(false)->after('cost_price');
            $table->boolean('track_expiry')->default(false)->after('track_batch');
        });

        Schema::create('unit_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('from_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('to_unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('multiplier', 18, 6);
            $table->string('conversion_key', 100)->unique();
            $table->timestamps();
            $table->index(['inventory_item_id', 'from_unit_id', 'to_unit_id'], 'unit_conversion_lookup');
        });

        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->decimal('current_stock', 15, 3)->default(0);
            $table->decimal('average_cost', 15, 2)->default(0);
            $table->timestamps();
            $table->unique(['warehouse_id', 'inventory_item_id']);
            $table->index(['warehouse_id', 'current_stock']);
        });

        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->string('status', 20)->default('confirmed')->index()->after('type');
            $table->string('direction', 10)->default('mixed')->index()->after('status');
            $table->foreignId('warehouse_id')->nullable()->after('direction')->constrained()->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->after('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->after('reference_id')->constrained('inventory_transactions')->restrictOnDelete();
            $table->string('idempotency_key', 120)->nullable()->unique()->after('reversal_of_id');
            $table->dateTime('occurred_at')->nullable()->index()->after('reason');
            $table->foreignId('confirmed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
            $table->json('metadata')->nullable()->after('confirmed_at');
            $table->index(['warehouse_id', 'type', 'occurred_at'], 'inventory_ledger_reporting');
        });

        Schema::table('inventory_transaction_items', function (Blueprint $table) {
            $table->foreignId('inventory_stock_id')->nullable()->after('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->after('quantity')->constrained('units')->restrictOnDelete();
            $table->decimal('stock_value', 18, 2)->default(0)->after('unit_cost');
            $table->string('cost_source', 30)->default('legacy')->after('stock_value');
            $table->boolean('cost_review_required')->default(false)->index()->after('cost_source');
            $table->text('note')->nullable()->after('cost_review_required');
            $table->index(['inventory_item_id', 'created_at'], 'inventory_item_ledger_date');
        });

        Schema::create('inventory_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_code', 40)->unique();
            $table->string('type', 30)->index();
            $table->string('status', 20)->default('draft')->index();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->nullable()->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->restrictOnDelete();
            $table->dateTime('transaction_date')->index();
            $table->string('reason', 255)->nullable();
            $table->string('recipient')->nullable();
            $table->text('note')->nullable();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['type', 'status', 'transaction_date'], 'inventory_document_lookup');
        });

        Schema::create('inventory_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->decimal('base_quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->string('batch_code', 60)->nullable();
            $table->date('manufactured_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('reason', 255)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['inventory_document_id', 'inventory_item_id'], 'inventory_document_lines');
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_document_item_id')->nullable()->constrained('inventory_document_items')->nullOnDelete();
            $table->string('batch_code', 60);
            $table->decimal('initial_quantity', 15, 3);
            $table->decimal('remaining_quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->date('manufactured_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->unique(['warehouse_id', 'inventory_item_id', 'batch_code'], 'inventory_batch_identity');
            $table->index(['warehouse_id', 'inventory_item_id', 'expiry_date'], 'inventory_batch_fefo');
        });

        Schema::create('inventory_batch_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_transaction_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->timestamps();
            $table->unique(['inventory_transaction_item_id', 'inventory_batch_id'], 'inventory_batch_movement_line');
        });

        Schema::create('stocktakes', function (Blueprint $table) {
            $table->id();
            $table->string('stocktake_code', 40)->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->dateTime('counted_at')->index();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stocktake_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stocktake_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->decimal('system_quantity', 15, 3);
            $table->decimal('physical_quantity', 15, 3)->nullable();
            $table->decimal('difference_quantity', 15, 3)->nullable();
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('difference_value', 18, 2)->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();
            $table->unique(['stocktake_id', 'inventory_item_id']);
        });

        Schema::create('inventory_order_warnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 50)->index();
            $table->string('message', 500);
            $table->json('context')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['order_id', 'product_id', 'code'], 'inventory_order_warning_once');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('yield_unit_id')->nullable()->after('yield_quantity')->constrained('units')->restrictOnDelete();
            $table->unsignedInteger('version')->default(1)->after('yield_unit_id');
            $table->string('status', 20)->default('active')->index()->after('version');
            $table->text('note')->nullable()->after('status');
        });

        Schema::table('recipe_items', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('unit')->constrained('units')->restrictOnDelete();
            $table->decimal('wastage_percent', 5, 2)->default(0)->after('unit_id');
        });

        if (DB::connection()->pretending()) {
            return;
        }

        $now = now();
        $units = [
            ['code' => 'kg', 'name' => 'Kilogram', 'symbol' => 'kg', 'category' => 'mass', 'decimal_places' => 3],
            ['code' => 'g', 'name' => 'Gram', 'symbol' => 'g', 'category' => 'mass', 'decimal_places' => 3],
            ['code' => 'l', 'name' => 'L?t', 'symbol' => 'l?t', 'category' => 'volume', 'decimal_places' => 3],
            ['code' => 'ml', 'name' => 'Millil?t', 'symbol' => 'ml', 'category' => 'volume', 'decimal_places' => 3],
            ['code' => 'chai', 'name' => 'Chai', 'symbol' => 'chai', 'category' => 'count', 'decimal_places' => 0],
            ['code' => 'lon', 'name' => 'Lon', 'symbol' => 'lon', 'category' => 'count', 'decimal_places' => 0],
            ['code' => 'thung', 'name' => 'Th?ng', 'symbol' => 'th?ng', 'category' => 'package', 'decimal_places' => 0],
            ['code' => 'goi', 'name' => 'G?i', 'symbol' => 'g?i', 'category' => 'count', 'decimal_places' => 0],
            ['code' => 'cai', 'name' => 'C?i', 'symbol' => 'c?i', 'category' => 'count', 'decimal_places' => 0],
            ['code' => 'phan', 'name' => 'Ph?n', 'symbol' => 'ph?n', 'category' => 'count', 'decimal_places' => 0],
        ];
        foreach ($units as $unit) {
            DB::table('units')->updateOrInsert(['code' => $unit['code']], $unit + ['status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('warehouses')->insert(['code' => 'MAIN', 'name' => 'Kho chính', 'is_default' => true, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
        $warehouseId = (int) DB::table('warehouses')->where('code', 'MAIN')->value('id');

        foreach (DB::table('inventory_items')->orderBy('id')->get() as $item) {
            $normalized = mb_strtolower(Str::ascii(trim((string) $item->unit)));
            $code = match ($normalized) {
                'l?t', 'lit', 'liter', 'litre' => 'l',
                'g?i', 'goi' => 'goi',
                'th?ng', 'thung' => 'thung',
                'c?i', 'cai' => 'cai',
                'ph?n', 'phan' => 'phan',
                default => preg_replace('/[^a-z0-9_-]+/u', '-', $normalized) ?: 'unit-' . $item->id,
            };
            $unitId = DB::table('units')->where('code', $code)->value('id');
            if (! $unitId) {
                $unitId = DB::table('units')->insertGetId(['code' => mb_substr($code, 0, 30), 'name' => $item->unit, 'symbol' => $item->unit, 'category' => 'custom', 'decimal_places' => 3, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now]);
            }
            DB::table('inventory_items')->where('id', $item->id)->update(['base_unit_id' => $unitId]);
            DB::table('inventory_stocks')->insert(['warehouse_id' => $warehouseId, 'inventory_item_id' => $item->id, 'current_stock' => $item->current_stock, 'average_cost' => $item->cost_price, 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('suppliers')->orderBy('id')->each(function ($supplier): void {
            DB::table('suppliers')->where('id', $supplier->id)->update(['code' => 'NCC-' . str_pad((string) $supplier->id, 4, '0', STR_PAD_LEFT)]);
        });

        $unitIds = DB::table('units')->pluck('id', 'code');
        DB::table('unit_conversions')->insertOrIgnore([
            ['inventory_item_id' => null, 'from_unit_id' => $unitIds['kg'], 'to_unit_id' => $unitIds['g'], 'multiplier' => 1000, 'conversion_key' => 'global:kg:g', 'created_at' => $now, 'updated_at' => $now],
            ['inventory_item_id' => null, 'from_unit_id' => $unitIds['g'], 'to_unit_id' => $unitIds['kg'], 'multiplier' => 0.001, 'conversion_key' => 'global:g:kg', 'created_at' => $now, 'updated_at' => $now],
            ['inventory_item_id' => null, 'from_unit_id' => $unitIds['l'], 'to_unit_id' => $unitIds['ml'], 'multiplier' => 1000, 'conversion_key' => 'global:l:ml', 'created_at' => $now, 'updated_at' => $now],
            ['inventory_item_id' => null, 'from_unit_id' => $unitIds['ml'], 'to_unit_id' => $unitIds['l'], 'multiplier' => 0.001, 'conversion_key' => 'global:ml:l', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('recipes')->update(['yield_unit_id' => $unitIds['phan'] ?? null]);
        DB::table('recipe_items')->orderBy('id')->each(function ($line): void {
            DB::table('recipe_items')->where('id', $line->id)->update(['unit_id' => DB::table('inventory_items')->where('id', $line->inventory_item_id)->value('base_unit_id')]);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE inventory_transactions DROP CHECK chk_inventory_transactions_type');
        }
        DB::table('inventory_transactions')->orderBy('id')->each(function ($transaction) use ($warehouseId): void {
            $quantity = (float) DB::table('inventory_transaction_items')->where('inventory_transaction_id', $transaction->id)->sum('quantity');
            $type = match ($transaction->type) {
                'adjustment' => $quantity >= 0 ? 'adjustment_in' : 'adjustment_out',
                'return' => 'customer_return',
                default => $transaction->type
            };
            $direction = in_array($type, ['in', 'adjustment_in', 'transfer_in', 'customer_return'], true) ? 'in' : 'out';
            DB::table('inventory_transactions')->where('id', $transaction->id)->update(['type' => $type, 'direction' => $direction, 'warehouse_id' => $warehouseId, 'occurred_at' => $transaction->created_at, 'confirmed_at' => $transaction->created_at, 'confirmed_by' => $transaction->created_by]);
        });
        DB::table('inventory_transaction_items')->orderBy('id')->each(function ($line) use ($warehouseId): void {
            $item = DB::table('inventory_items')->where('id', $line->inventory_item_id)->first();
            $stockId = DB::table('inventory_stocks')->where('warehouse_id', $warehouseId)->where('inventory_item_id', $line->inventory_item_id)->value('id');
            $type = DB::table('inventory_transactions')->where('id', $line->inventory_transaction_id)->value('type');
            $review = $type === 'adjustment_in' && $line->unit_cost !== null && abs((float) $line->unit_cost - (float) $item->cost_price) > 0.01;
            DB::table('inventory_transaction_items')->where('id', $line->id)->update(['inventory_stock_id' => $stockId, 'unit_id' => $item->base_unit_id, 'stock_value' => abs((float) $line->quantity) * (float) ($line->unit_cost ?? $item->cost_price), 'cost_source' => 'legacy', 'cost_review_required' => $review, 'note' => $review ? 'Gi? v?n legacy kh?c gi? v?n m?t h?ng; c?n x?c minh, ch?a t? ??ng s?a.' : null]);
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_transactions ADD CONSTRAINT chk_inventory_transactions_type CHECK (type IN ('in','out','adjustment_in','adjustment_out','transfer_out','transfer_in','stocktake_adjustment','waste','return_to_supplier','customer_return'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE inventory_transactions DROP CHECK chk_inventory_transactions_type');
            DB::statement("ALTER TABLE inventory_transactions ADD CONSTRAINT chk_inventory_transactions_type CHECK (type IN ('in','out','adjustment','waste','return'))");
        }
        Schema::table('recipe_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
            $table->dropColumn('wastage_percent');
        });
    }
};
