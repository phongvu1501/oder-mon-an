<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('status', 20)->default('active')->index()->after('password');
            $table->unsignedInteger('token_version')->default(1)->after('status');
            $table->timestamp('last_login_at')->nullable()->after('token_version');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'status', 'token_version', 'last_login_at', 'deleted_at']);
        });
    }
};
