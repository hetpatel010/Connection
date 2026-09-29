<?php

namespace App\Domain\Inventory\Enums;

enum StockDirection: string
{
    case In = 'in';
    case Out = 'out';
}
