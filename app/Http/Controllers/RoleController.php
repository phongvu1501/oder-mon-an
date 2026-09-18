<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(): View
    {
        return view('roles.index', ['roles' => Role::withCount(['users', 'permissions'])->paginate(config('restaurant.pagination'))]);
    }

    public function create(): View
    {
        return view('roles.form', ['role' => new Role, 'permissions' => Permission::orderBy('module')->get()->groupBy('module')]);
    }

    public function show(Role $role): View
    {
        return view('roles.show', ['role' => $role->load('permissions', 'users')]);
    }

    public function edit(Role $role): View
    {
        return view('roles.form', ['role' => $role->load('permissions'), 'permissions' => Permission::orderBy('module')->get()->groupBy('module')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->data($request);
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $role = Role::create($data);
        $role->permissions()->sync($permissions);
        $this->activity->write('create', 'roles', $role, new: $role->load('permissions')->toArray());

        return redirect()->route('roles.index')->with('success', 'Đã tạo vai trò.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $old = $role->load('permissions')->toArray();
        $data = $this->data($request, $role);
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $role->update($data);
        $role->permissions()->sync($permissions);
        $this->activity->write('update_permissions', 'roles', $role, $old, $role->fresh()->load('permissions')->toArray());

        return redirect()->route('roles.index')->with('success', 'Đã cập nhật vai trò và quyền.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Không thể xóa vai trò đang được gán.');
        }
        $role->delete();

        return back()->with('success', 'Đã xóa vai trò chưa sử dụng.');
    }

    private function data(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('roles')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:1000'], 'status' => ['required', Rule::in(['active', 'inactive'])],
            'permissions' => ['nullable', 'array'], 'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
    }
}
