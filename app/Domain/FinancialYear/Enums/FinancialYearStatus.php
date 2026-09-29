<?php

namespace App\Domain\FinancialYear\Enums;

enum FinancialYearStatus: string
{
    case Open = 'open';
    case Closing = 'closing';
    case Closed = 'closed';

    public function isLocked(): bool
    {
        return $this === self::Closed || $this === self::Closing;
    }

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Closing => 'Closing',
            self::Closed => 'Closed',
        };
    }
}
