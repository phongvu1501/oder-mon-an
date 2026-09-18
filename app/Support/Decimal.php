<?php

namespace App\Support;

use InvalidArgumentException;

final class Decimal
{
    public static function toMinor(string|int|float $value, int $scale = 2): int
    {
        $normalized = trim((string) $value);

        if (! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/', $normalized, $matches)) {
            throw new InvalidArgumentException('Invalid decimal value.');
        }

        $fraction = substr(str_pad($matches[3] ?? '', $scale, '0'), 0, $scale);
        $minor = ((int) $matches[2] * (10 ** $scale)) + (int) $fraction;

        return ($matches[1] ?? '') === '-' ? -$minor : $minor;
    }

    public static function fromMinor(int $value, int $scale = 2): string
    {
        $factor = 10 ** $scale;
        $sign = $value < 0 ? '-' : '';
        $absolute = abs($value);

        return $sign.intdiv($absolute, $factor).'.'.str_pad((string) ($absolute % $factor), $scale, '0', STR_PAD_LEFT);
    }
}
