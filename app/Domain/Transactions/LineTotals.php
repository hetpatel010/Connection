<?php

namespace App\Domain\Transactions;

final class LineTotals
{
    /**
     * @param  array<int, array{quantity: float|string, unit_price: int, tax_rate: float|string}>  $lines
     * @return array{subtotal: int, tax: int, total: int, lines: list<array{quantity: float, unit_price: int, tax_rate: float, subtotal: int, tax: int, total: int}>}
     */
    public static function fromLines(array $lines): array
    {
        $subtotal = 0;
        $tax = 0;
        $computedLines = [];

        foreach ($lines as $line) {
            $quantity = (float) $line['quantity'];
            $unitPrice = (int) $line['unit_price'];
            $taxRate = (float) $line['tax_rate'];
            $lineSubtotal = (int) round($quantity * $unitPrice);
            $lineTax = (int) round($lineSubtotal * $taxRate / 100);
            $lineTotal = $lineSubtotal + $lineTax;

            $subtotal += $lineSubtotal;
            $tax += $lineTax;

            $computedLines[] = [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'subtotal' => $lineSubtotal,
                'tax' => $lineTax,
                'total' => $lineTotal,
            ];
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $subtotal + $tax,
            'lines' => $computedLines,
        ];
    }
}
