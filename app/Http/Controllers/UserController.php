<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function index(Request $request): View
    {
        $users = User::with('roles')->when($request->filled('q'), fn ($q) => $q->where(fn ($i) => $i->where('name', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()->paginate(config('restaurant.pagination'))->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User, 'roles' => Role::where('status', 'active')->get()]);
    }

    public function show(User $user): View
    {
        return view('users.show', ['user' => $user->load('roles.permissions')]);
    }

    public function edit(User $user): View
    {
        return view('users.form', ['user' => $user->load('roles'), 'roles' => Role::where('status', 'active')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->data($request);
        $roles = $data['roles'];
        unset($data['roles']);
        $user = User::create($data);
        $user->roles()->sync($roles);
        $this->activity->write('create', 'users', $user, new: $user->load('roles')->toArray());

        return redirect()->route('users.index')->with('success', 'Đã tạo tài khoản.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $old = $user->load('roles')->toArray();
        $data = $this->data($request, $user);
        $roles = $data['roles'];
        unset($data['roles']);
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['token_version'] = $user->token_version + 1;
        }
        $user->update($data);
        $user->roles()->sync($roles);
        $this->activity->write('update', 'users', $user, $old, $user->fresh()->load('roles')->toArray());

        return redirect()->route('users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Không thể tự khóa tài khoản đang sử dụng.');
        }
        $user->update(['status' => 'inactive', 'token_version' => $user->token_version + 1]);
        $user->delete();
        $this->activity->write('delete', 'users', $user);

        return back()->with('success', 'Đã khóa và lưu trữ tài khoản.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $user->update(['password' => Hash::make($data['password']), 'token_version' => $user->token_version + 1]);
        $this->activity->write('reset_password', 'users', $user);

        return back()->with('success', 'Đã đặt lại mật khẩu và thu hồi token cũ.');
    }

    private function data(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'roles' => ['required', 'array', 'min:1'], 'roles.*' => ['integer', 'exists:roles,id'],
        ]);
    }
}
