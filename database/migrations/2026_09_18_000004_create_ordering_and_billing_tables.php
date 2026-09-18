<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code', 40)->unique();
            $table->foreignId('table_session_id')->constrained()->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('customer_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('inventory_deducted_at')->nullable();
            $table->timestamps();
            $table->unique(['table_session_id', 'idempotency_key']);
            $table->index(['table_session_id', 'status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('unit_price', 15, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('subtotal', 15, 2);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('bill_code', 40)->unique();
            $table->foreignId('table_session_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('unpaid')->index();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code', 40)->unique();
            $table->uuid('request_id')->unique();
            $table->foreignId('table_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('bill_id')->constrained()->restrictOnDelete();
            $table->string('method', 30)->index();
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->default('pending')->index();
            $table->string('transaction_reference')->nullable()->index();
            $table->text('payment_note')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
            $table->index(['bill_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bills');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
