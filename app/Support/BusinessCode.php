<?php

namespace App\Support;

use Illuminate\Support\Str;

final class BusinessCode
{
    public static function make(string $prefix): string
    {
        return strtoupper($prefix).'-'.now()->format('ymdHis').'-'.Str::upper(Str::random(5));
    }
}
