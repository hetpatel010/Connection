<?php

namespace App\Domain\Accounting\Enums;

enum BalanceSide: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
