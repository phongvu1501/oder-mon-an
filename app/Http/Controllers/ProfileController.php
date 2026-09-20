<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ProfileController extends Controller
{
    public function __construct(private readonly ActivityLogService $activity) {}

    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()->load('roles')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $old = $user->only(['name', 'email', 'phone', 'avatar']);

        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars', 'public');
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $newAvatar;
        } else {
            unset($data['avatar']);
        }

        $user->update($data);
        $this->activity->write('update_profile', 'users', $user, $old, $user->fresh()->only(['name', 'email', 'phone', 'avatar']));

        return back()->with('success', 'Thông tin cá nhân đã được cập nhật.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);
        $user = $request->user();

        try {
            $matches = Hash::check($data['current_password'], $user->password);
        } catch (RuntimeException) {
            $matches = false;
        }
        if (! $matches) {
            throw ValidationException::withMessages(['current_password' => 'Mật khẩu hiện tại không chính xác.']);
        }

        $user->forceFill(['password' => $data['password']])->save();
        $user->increment('token_version');
        $this->activity->write('change_password', 'users', $user);

        return redirect()->route('login')
            ->with('success', 'Mật khẩu đã đổi. Vui lòng đăng nhập lại.')
            ->withoutCookie(config('jwt.cookie'))
            ->withoutCookie('refresh_token', '/api/auth');
    }
}
