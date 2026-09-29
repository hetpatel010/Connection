<?php

namespace App\Support;

final class Money
{
    public static function rupeesToPaise(float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function paiseToRupees(int $paise): float
    {
        return round($paise / 100, 2);
    }

    public static function format(int $paise, string $currency = 'INR'): string
    {
        $symbol = match ($currency) {
            'INR' => '₹',
            default => $currency.' ',
        };

        $amount = number_format(abs(self::paiseToRupees($paise)), 2);
        $prefix = $paise < 0 ? '-' : '';

        return $prefix.$symbol.$amount;
    }
}
