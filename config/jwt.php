<?php

return [
    'secret' => env('JWT_SECRET', env('APP_KEY')),
    'algorithm' => 'HS256',
    'issuer' => env('JWT_ISSUER', env('APP_URL', 'oder-monan')),
    'access_ttl' => (int) env('JWT_TTL', 60),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 20160),
    'cookie' => env('JWT_COOKIE', 'access_token'),
];
