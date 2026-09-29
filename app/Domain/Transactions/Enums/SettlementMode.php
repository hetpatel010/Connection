<?php

namespace App\Domain\Transactions\Enums;

enum SettlementMode: string
{
    case Credit = 'credit';
    case Cash = 'cash';

    public function isCredit(): bool
    {
        return $this === self::Credit;
    }
}
