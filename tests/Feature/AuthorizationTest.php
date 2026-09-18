<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_jwt_returns_unauthorized_for_api(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized()->assertJsonPath('success', false);
    }

    public function test_direct_url_access_without_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Limited', 'slug' => 'limited', 'status' => 'active']);
        $user->roles()->attach($role);
        $token = app(JwtService::class)->accessToken($user);
        $this->withHeader('Authorization', 'Bearer '.$token)->get('/admin/products')->assertForbidden();
    }

    public function test_user_with_permission_can_access_direct_url(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Catalog', 'slug' => 'catalog', 'status' => 'active']);
        $permission = Permission::create(['name' => 'products.view', 'label' => 'View products', 'module' => 'products']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);
        $token = app(JwtService::class)->accessToken($user);
        $this->withHeader('Authorization', 'Bearer '.$token)->get('/admin/products')->assertOk();
    }

    public function test_token_version_revokes_existing_jwt(): void
    {
        $user = User::factory()->create();
        $token = app(JwtService::class)->accessToken($user);
        $user->increment('token_version');
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/auth/me')->assertUnauthorized();
    }
}
