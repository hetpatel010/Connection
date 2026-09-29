<?php

namespace App\Domain\Inventory\Enums;

enum StockMovementType: string
{
    case Opening = 'opening';
    case Sale = 'sale';
    case Purchase = 'purchase';
    case Adjustment = 'adjustment';
    case Reversal = 'reversal';
    case SaleReturn = 'sale_return';
    case PurchaseReturn = 'purchase_return';
    case TransferIn = 'stock_transfer_in';
    case TransferOut = 'stock_transfer_out';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Opening Stock',
            self::Sale => 'Sale',
            self::Purchase => 'Purchase',
            self::Adjustment => 'Stock Adjustment',
            self::Reversal => 'Reversal',
            self::SaleReturn => 'Sales Return',
            self::PurchaseReturn => 'Purchase Return',
            self::TransferIn => 'Stock Transfer In',
            self::TransferOut => 'Stock Transfer Out',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function filterOptions(): array
    {
        return [
            ['value' => self::Opening->value, 'label' => self::Opening->label()],
            ['value' => self::Purchase->value, 'label' => self::Purchase->label()],
            ['value' => self::PurchaseReturn->value, 'label' => self::PurchaseReturn->label()],
            ['value' => self::Sale->value, 'label' => self::Sale->label()],
            ['value' => self::SaleReturn->value, 'label' => self::SaleReturn->label()],
            ['value' => 'adjustment:in', 'label' => 'Stock Adjustment In'],
            ['value' => 'adjustment:out', 'label' => 'Stock Adjustment Out'],
            ['value' => self::TransferIn->value, 'label' => self::TransferIn->label()],
            ['value' => self::TransferOut->value, 'label' => self::TransferOut->label()],
            ['value' => self::Other->value, 'label' => self::Other->label()],
        ];
    }
}
