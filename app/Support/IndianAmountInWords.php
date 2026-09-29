<?php

namespace App\Support;

final class IndianAmountInWords
{
    /** @var list<string> */
    private const ONES = [
        '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
        'Seventeen', 'Eighteen', 'Nineteen',
    ];

    /** @var list<string> */
    private const TENS = [
        '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
    ];

    public static function fromPaise(int $paise): string
    {
        if ($paise <= 0) {
            return __('Rupees Zero Only');
        }

        $rupees = intdiv($paise, 100);
        $paisePart = $paise % 100;

        $words = __('Rupees :amount', ['amount' => self::convert($rupees)]);

        if ($paisePart > 0) {
            $words .= ' '.__('and :amount Paise', ['amount' => self::convert($paisePart)]);
        }

        return $words.' '.__('Only');
    }

    private static function convert(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }

        $parts = [];

        $crores = intdiv($number, 10000000);
        $number %= 10000000;
        $lakhs = intdiv($number, 100000);
        $number %= 100000;
        $thousands = intdiv($number, 1000);
        $number %= 1000;
        $hundreds = intdiv($number, 100);
        $remainder = $number % 100;

        if ($crores > 0) {
            $parts[] = self::convertTwoDigits($crores).' Crore';
        }

        if ($lakhs > 0) {
            $parts[] = self::convertTwoDigits($lakhs).' Lakh';
        }

        if ($thousands > 0) {
            $parts[] = self::convertTwoDigits($thousands).' Thousand';
        }

        if ($hundreds > 0) {
            $parts[] = self::ONES[$hundreds].' Hundred';
        }

        if ($remainder > 0) {
            if ($parts !== []) {
                $parts[] = 'and';
            }

            $parts[] = self::convertTwoDigits($remainder);
        }

        return implode(' ', $parts);
    }

    private static function convertTwoDigits(int $number): string
    {
        if ($number < 20) {
            return self::ONES[$number];
        }

        $ten = intdiv($number, 10);
        $one = $number % 10;

        return trim(self::TENS[$ten].($one > 0 ? ' '.self::ONES[$one] : ''));
    }
}
