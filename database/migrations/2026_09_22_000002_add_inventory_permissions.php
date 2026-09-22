<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $actions = ['view', 'create', 'update', 'delete', 'receive', 'issue', 'adjust', 'stocktake', 'transfer', 'waste', 'confirm', 'cancel', 'report', 'export', 'manage'];
        foreach ($actions as $action) {
            DB::table('permissions')->updateOrInsert(
                ['name' => "inventory.{$action}"],
                ['label' => ucfirst($action) . ' inventory', 'module' => 'inventory', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $permissionIds = DB::table('permissions')->where('module', 'inventory')->pluck('id');
        $roleIds = DB::table('roles')->whereIn('slug', ['super-admin', 'admin', 'manager'])->pluck('id');
        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permissionId]);
            }
        }
    }

    public function down(): void
    {
        $names = collect(['view', 'create', 'update', 'delete', 'receive', 'issue', 'adjust', 'stocktake', 'transfer', 'waste', 'confirm', 'cancel', 'report', 'export', 'manage'])
            ->map(fn($action) => "inventory.{$action}");
        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
