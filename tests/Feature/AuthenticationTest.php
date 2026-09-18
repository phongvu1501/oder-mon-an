<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_and_receive_jwt(): void
    {
        User::factory()->create(['email' => 'staff@example.test', 'password' => Hash::make('Password@123')]);
        $this->postJson('/api/auth/login', ['email' => 'staff@example.test', 'password' => 'Password@123'])
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'expires_in']]);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'staff@example.test', 'password' => Hash::make('Password@123')]);
        $this->postJson('/api/auth/login', ['email' => 'staff@example.test', 'password' => 'wrong-password'])
            ->assertUnprocessable()->assertJsonPath('success', false);
    }

    public function test_legacy_non_bcrypt_password_is_rejected_without_runtime_exception(): void
    {
        DB::table('users')->insert([
            'name' => 'Legacy User',
            'email' => 'legacy@example.test',
            'password' => 'legacy-plaintext',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->postJson('/api/auth/login', ['email' => 'legacy@example.test', 'password' => 'legacy-plaintext'])
            ->assertUnprocessable()->assertJsonPath('success', false);
    }

    public function test_inactive_user_is_rejected(): void
    {
        User::factory()->create(['email' => 'inactive@example.test', 'password' => Hash::make('Password@123'), 'status' => 'inactive']);
        $this->postJson('/api/auth/login', ['email' => 'inactive@example.test', 'password' => 'Password@123'])
            ->assertUnprocessable();
    }
}
