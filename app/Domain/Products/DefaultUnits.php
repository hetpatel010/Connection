<?php

namespace App\Domain\Products;

final class DefaultUnits
{
    /**
     * @return list<array{name: string, symbol: string, decimal_places: int, is_default: bool}>
     */
    public static function definitions(): array
    {
        return [
            ['name' => 'Pieces', 'symbol' => 'Pcs', 'decimal_places' => 0, 'is_default' => true],
            ['name' => 'Box', 'symbol' => 'Box', 'decimal_places' => 0, 'is_default' => false],
            ['name' => 'Kilogram', 'symbol' => 'Kg', 'decimal_places' => 3, 'is_default' => false],
            ['name' => 'Gram', 'symbol' => 'g', 'decimal_places' => 0, 'is_default' => false],
            ['name' => 'Litre', 'symbol' => 'Ltr', 'decimal_places' => 3, 'is_default' => false],
            ['name' => 'Metre', 'symbol' => 'Mtr', 'decimal_places' => 2, 'is_default' => false],
            ['name' => 'Dozen', 'symbol' => 'Doz', 'decimal_places' => 0, 'is_default' => false],
        ];
    }
}
