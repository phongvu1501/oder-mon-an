<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt, private readonly ActivityLogService $activity) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $user = User::query()->where('email', $request->string('email'))->first();
        $passwordMatches = false;

        if ($user) {
            try {
                $passwordMatches = Hash::check($request->string('password'), $user->password);
            } catch (\RuntimeException) {
                // Legacy/plaintext passwords are never accepted. An admin must reset them.
            }
        }

        if (! $user || $user->status !== RecordStatus::Active || ! $passwordMatches) {
            throw ValidationException::withMessages(['email' => 'Thông tin đăng nhập không chính xác hoặc tài khoản đã bị khóa.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();
        Auth::setUser($user);
        $this->activity->write('login', 'authentication', $user, request: $request);
        $tokens = $this->tokens($user);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true, 'message' => 'Đăng nhập thành công.', 'data' => $tokens]);
        }

        return $this->withCookies(redirect()->route('dashboard'), $tokens);
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $request->input('refresh_token') ?: $request->cookie('refresh_token');
        try {
            $payload = $this->jwt->decode((string) $token);
            if (($payload->typ ?? null) !== 'refresh') {
                throw new \RuntimeException('Invalid token type.');
            }
            $user = DB::transaction(function () use ($payload) {
                $user = User::query()->lockForUpdate()->findOrFail((int) $payload->sub);
                if ($user->status !== RecordStatus::Active || $user->token_version !== (int) $payload->ver) {
                    throw new \RuntimeException('Refresh token was revoked.');
                }
                $user->increment('token_version');

                return $user->refresh();
            });
        } catch (Throwable) {
            return response()->json(['success' => false, 'message' => 'Refresh token không hợp lệ hoặc đã hết hạn.'], 401);
        }

        $tokens = $this->tokens($user);

        return $this->withCookies(response()->json(['success' => true, 'message' => 'Đã làm mới token.', 'data' => $tokens]), $tokens);
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $this->activity->write('logout', 'authentication', $user, request: $request);
        $user->increment('token_version');
        $response = $request->expectsJson() || $request->is('api/*')
            ? response()->json(['success' => true, 'message' => 'Đã đăng xuất.'])
            : redirect()->route('login')->with('success', 'Đã đăng xuất.');

        return $this->forgetCookies($response);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'message' => 'Authenticated.', 'data' => $request->user()->load('roles.permissions')]);
    }

    private function tokens(User $user): array
    {
        return ['access_token' => $this->jwt->accessToken($user), 'refresh_token' => $this->jwt->refreshToken($user), 'token_type' => 'Bearer', 'expires_in' => (int) config('jwt.access_ttl') * 60];
    }

    private function withCookies($response, array $tokens)
    {
        return $response
            ->cookie(config('jwt.cookie'), $tokens['access_token'], config('jwt.access_ttl'), '/', null, request()->isSecure(), true, false, 'Strict')
            ->cookie('refresh_token', $tokens['refresh_token'], config('jwt.refresh_ttl'), '/api/auth', null, request()->isSecure(), true, false, 'Strict');
    }

    private function forgetCookies($response)
    {
        return $response->withoutCookie(config('jwt.cookie'))->withoutCookie('refresh_token', '/api/auth');
    }
}
