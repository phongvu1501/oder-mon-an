<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('sku', 50)->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 15, 2);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->string('unit', 30)->default('phan');
            $table->string('status', 20)->default('active')->index();
            $table->boolean('is_available')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'status', 'is_available']);
        });

        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->unsignedSmallInteger('capacity')->default(4);
            $table->string('location')->nullable();
            $table->string('status', 20)->default('available')->index();
            $table->string('qr_token', 64)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_table_id')->constrained()->restrictOnDelete();
            $table->string('session_code', 40)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->unsignedSmallInteger('guest_count')->default(1);
            $table->timestamp('opened_at')->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->timestamps();
            $table->index(['restaurant_table_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_sessions');
        Schema::dropIfExists('restaurant_tables');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
