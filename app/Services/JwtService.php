<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use RuntimeException;
use stdClass;

class JwtService
{
    public function accessToken(User $user): string
    {
        return $this->issue($user, 'access', (int) config('jwt.access_ttl'));
    }

    public function refreshToken(User $user): string
    {
        return $this->issue($user, 'refresh', (int) config('jwt.refresh_ttl'));
    }

    public function decode(string $token): stdClass
    {
        JWT::$leeway = 30;

        return JWT::decode($token, new Key($this->secret(), config('jwt.algorithm')));
    }

    private function issue(User $user, string $type, int $ttlMinutes): string
    {
        $now = now()->timestamp;

        return JWT::encode([
            'iss' => config('jwt.issuer'),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($ttlMinutes * 60),
            'sub' => (string) $user->getKey(),
            'jti' => (string) Str::uuid(),
            'typ' => $type,
            'ver' => (int) ($user->token_version ?? 1),
        ], $this->secret(), config('jwt.algorithm'));
    }

    private function secret(): string
    {
        $secret = (string) config('jwt.secret');

        if (str_starts_with($secret, 'base64:')) {
            $secret = (string) base64_decode(substr($secret, 7), true);
        }

        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET must contain at least 32 bytes.');
        }

        return $secret;
    }
}
