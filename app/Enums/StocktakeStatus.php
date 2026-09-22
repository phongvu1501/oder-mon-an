<?php

namespace App\Enums;

enum StocktakeStatus: string
{
    case Draft = 'draft';
    case Counting = 'counting';
    case Review = 'review';
    case Confirmed = 'confirmed';

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::Draft => [self::Counting], self::Counting => [self::Review],
            self::Review => [self::Counting, self::Confirmed], self::Confirmed => [],
        }, true);
    }
}
