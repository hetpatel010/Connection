<?php

namespace App\Domain\Transactions\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Reversed = 'reversed';

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isConfirmed(): bool
    {
        return $this === self::Confirmed;
    }
}
