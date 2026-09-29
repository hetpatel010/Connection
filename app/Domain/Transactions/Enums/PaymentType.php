<?php

namespace App\Domain\Transactions\Enums;

enum PaymentType: string
{
    case PaymentIn = 'payment_in';
    case PaymentOut = 'payment_out';

    public function label(): string
    {
        return match ($this) {
            self::PaymentIn => 'Payment In',
            self::PaymentOut => 'Payment Out',
        };
    }
}
