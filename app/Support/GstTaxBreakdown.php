<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Party;
use App\Models\PurchaseBillLine;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;

final class GstTaxBreakdown
{
    /**
     * @return array{
     *     is_inter_state: bool,
     *     cgst: int,
     *     sgst: int,
     *     igst: int,
     *     total_tax: int,
     *     place_of_supply: string,
     *     hsn_summary: list<array{
     *         hsn: string,
     *         tax_rate: float,
     *         taxable: int,
     *         cgst: int,
     *         sgst: int,
     *         igst: int,
     *         total_tax: int
     *     }>
     * }
     */
    public static function forInvoice(SalesInvoice $invoice, Business $business): array
    {
        $invoice->loadMissing(['party', 'lines']);

        $totalTax = (int) $invoice->tax_amount;
        $isInterState = self::isInterState($business, $invoice->party);

        if ($isInterState) {
            return [
                'is_inter_state' => true,
                'cgst' => 0,
                'sgst' => 0,
                'igst' => $totalTax,
                'total_tax' => $totalTax,
                'place_of_supply' => self::placeOfSupply($business, $invoice->party),
                'hsn_summary' => self::hsnSummary($invoice, $isInterState),
            ];
        }

        $half = (int) round($totalTax / 2);

        return [
            'is_inter_state' => false,
            'cgst' => $half,
            'sgst' => $totalTax - $half,
            'igst' => 0,
            'total_tax' => $totalTax,
            'place_of_supply' => self::placeOfSupply($business, $invoice->party),
            'hsn_summary' => self::hsnSummary($invoice, $isInterState),
        ];
    }

    /**
     * @return array{
     *     cgst_rate: float,
     *     sgst_rate: float,
     *     igst_rate: float,
     *     cgst: int,
     *     sgst: int,
     *     igst: int
     * }
     */
    public static function lineTaxAmounts(SalesInvoiceLine|PurchaseBillLine $line, bool $isInterState): array
    {
        $tax = (int) $line->tax_amount;
        $rate = (float) $line->tax_rate;

        if ($isInterState) {
            return [
                'cgst_rate' => 0,
                'sgst_rate' => 0,
                'igst_rate' => $rate,
                'cgst' => 0,
                'sgst' => 0,
                'igst' => $tax,
            ];
        }

        $halfRate = $rate / 2;
        $cgst = (int) round($tax / 2);

        return [
            'cgst_rate' => $halfRate,
            'sgst_rate' => $halfRate,
            'igst_rate' => 0,
            'cgst' => $cgst,
            'sgst' => $tax - $cgst,
            'igst' => 0,
        ];
    }

    public static function taxableAmount(SalesInvoiceLine|PurchaseBillLine $line): int
    {
        return (int) round((float) $line->quantity * $line->unit_price);
    }

    public static function stateCodeFromGstin(?string $gstin): ?string
    {
        if ($gstin === null) {
            return null;
        }

        $gstin = trim($gstin);

        if (strlen($gstin) < 2 || ! ctype_digit(substr($gstin, 0, 2))) {
            return null;
        }

        return substr($gstin, 0, 2);
    }

    public static function formattedState(?string $state, ?string $gstin): string
    {
        $code = self::stateCodeFromGstin($gstin);

        if ($state !== null && trim($state) !== '' && $code !== null) {
            return trim($state).' ('.$code.')';
        }

        if ($state !== null && trim($state) !== '') {
            return trim($state);
        }

        if ($code !== null) {
            return __('State Code :code', ['code' => $code]);
        }

        return '—';
    }

    public static function placeOfSupply(Business $business, Party $party): string
    {
        if ($party->billing_state) {
            return self::formattedState($party->billing_state, $party->gstin);
        }

        if ($party->gstin) {
            return self::formattedState(null, $party->gstin);
        }

        return self::formattedState($business->state, $business->gstin);
    }

    /**
     * @return list<array{
     *     hsn: string,
     *     tax_rate: float,
     *     taxable: int,
     *     cgst: int,
     *     sgst: int,
     *     igst: int,
     *     total_tax: int
     * }>
     */
    public static function hsnSummary(SalesInvoice $invoice, bool $isInterState): array
    {
        /** @var array<string, array{hsn: string, tax_rate: float, taxable: int, cgst: int, sgst: int, igst: int, total_tax: int}> $groups */
        $groups = [];

        foreach ($invoice->lines as $line) {
            $key = ($line->hsn_sac ?: '—').'|'.number_format((float) $line->tax_rate, 2, '.', '');
            $split = self::lineTaxAmounts($line, $isInterState);

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'hsn' => $line->hsn_sac ?: '—',
                    'tax_rate' => (float) $line->tax_rate,
                    'taxable' => 0,
                    'cgst' => 0,
                    'sgst' => 0,
                    'igst' => 0,
                    'total_tax' => 0,
                ];
            }

            $groups[$key]['taxable'] += self::taxableAmount($line);
            $groups[$key]['cgst'] += $split['cgst'];
            $groups[$key]['sgst'] += $split['sgst'];
            $groups[$key]['igst'] += $split['igst'];
            $groups[$key]['total_tax'] += (int) $line->tax_amount;
        }

        return array_values($groups);
    }

    public static function isInterState(Business $business, Party $party): bool
    {
        $businessState = self::normalizeState($business->state);
        $partyState = self::normalizeState($party->billing_state);

        if ($businessState === '' || $partyState === '') {
            return false;
        }

        return $businessState !== $partyState;
    }

    private static function normalizeState(?string $state): string
    {
        if ($state === null || trim($state) === '') {
            return '';
        }

        return strtolower(trim($state));
    }
}
