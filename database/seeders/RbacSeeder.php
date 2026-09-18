<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $definitions = [
            'dashboard' => ['view'], 'products' => ['view', 'create', 'update', 'delete'],
            'categories' => ['view', 'create', 'update', 'delete'], 'tables' => ['view', 'create', 'update', 'delete', 'qr'],
            'orders' => ['view', 'create', 'update', 'cancel', 'confirm'], 'payments' => ['view', 'create', 'confirm'],
            'inventory' => ['view', 'create', 'update', 'adjust'], 'suppliers' => ['view', 'create', 'update', 'delete'],
            'users' => ['view', 'create', 'update', 'delete'], 'roles' => ['view', 'create', 'update', 'delete'],
            'permissions' => ['view', 'manage'], 'reports' => ['view'], 'activity_logs' => ['view'],
        ];
        foreach ($definitions as $module => $actions) {
            foreach ($actions as $action) {
                Permission::updateOrCreate(['name' => "{$module}.{$action}"], ['label' => ucfirst($action).' '.str_replace('_', ' ', $module), 'module' => $module]);
            }
        }

        $roles = [
            'super-admin' => ['Super Admin', ['*']], 'admin' => ['Admin', ['*']],
            'manager' => ['Manager', ['dashboard.view', 'products.*', 'categories.*', 'tables.*', 'orders.*', 'payments.*', 'inventory.*', 'suppliers.*', 'users.view', 'users.update', 'reports.view', 'activity_logs.view']],
            'staff' => ['Staff', ['dashboard.view', 'tables.view', 'orders.view', 'orders.update', 'orders.cancel', 'orders.confirm', 'payments.view', 'payments.create', 'payments.confirm']],
            'kitchen' => ['Kitchen', ['orders.view', 'orders.update']],
        ];
        $all = Permission::all();
        foreach ($roles as $slug => [$name, $patterns]) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $name, 'status' => 'active']);
            $matched = $patterns[0] === '*' ? $all : $all->filter(fn (Permission $permission) => collect($patterns)->contains(
                fn (string $pattern) => str_ends_with($pattern, '.*') ? str_starts_with($permission->name, substr($pattern, 0, -1)) : $permission->name === $pattern
            ));
            $role->permissions()->sync($matched->modelKeys());
        }
    }
}
