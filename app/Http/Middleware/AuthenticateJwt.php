<?php

namespace App\Http\Middleware;

use App\Enums\RecordStatus;
use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateJwt
{
    public function __construct(private readonly JwtService $jwt) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->cookie(config('jwt.cookie'));
        if (! $token) {
            return $this->unauthenticated($request);
        }

        try {
            $payload = $this->jwt->decode($token);
        } catch (Throwable) {
            return $this->unauthenticated($request);
        }

        if (($payload->typ ?? null) !== 'access') {
            return $this->unauthenticated($request);
        }
        $user = User::query()->find((int) $payload->sub);
        if (! $user || $user->status !== RecordStatus::Active || (int) $payload->ver !== (int) $user->token_version) {
            return $this->unauthenticated($request);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('login')->with('error', 'Phien dang nhap da het han.');
    }
}
