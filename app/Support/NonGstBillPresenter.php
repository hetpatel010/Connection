<?php

namespace App\Support;

use App\Models\Business;
use App\Models\SalesInvoice;

final class NonGstBillPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function vars(SalesInvoice $invoice, Business $business, array $overrides = []): array
    {
        $isPurchase = $overrides['isPurchaseDocument'] ?? false;

        $businessAddress = collect([
            $business->address_line_1,
            $business->address_line_2,
            collect([$business->city, $business->state, $business->postal_code])->filter()->implode(', '),
        ])->filter()->implode("\n");

        $partyAddress = collect([
            $invoice->party->billing_address_line_1 ?? null,
            $invoice->party->billing_address_line_2 ?? null,
            collect([
                $invoice->party->billing_city ?? null,
                $invoice->party->billing_state ?? null,
                $invoice->party->billing_postal_code ?? null,
            ])->filter()->implode(', '),
        ])->filter()->implode("\n");

        $shipAddress = collect([
            $invoice->party->shipping_address_line_1 ?? null,
            $invoice->party->shipping_address_line_2 ?? null,
            collect([
                $invoice->party->shipping_city ?? null,
                $invoice->party->shipping_state ?? null,
                $invoice->party->shipping_postal_code ?? null,
            ])->filter()->implode(', '),
        ])->filter()->implode("\n") ?: ($isPurchase ? $businessAddress : $partyAddress);

        return array_merge($overrides, [
            'isPurchase' => $isPurchase,
            'amountInWords' => $overrides['amountInWords']
                ?? IndianAmountInWords::fromPaise((int) $invoice->total_amount),
            'businessAddress' => $businessAddress,
            'partyAddress' => $partyAddress,
            'shipAddress' => $shipAddress,
            'sellerName' => $isPurchase ? $invoice->party->name : ($business->legal_name ?: $business->name),
            'buyerName' => $isPurchase ? ($business->legal_name ?: $business->name) : $invoice->party->name,
            'sellerAddress' => $isPurchase ? $partyAddress : $businessAddress,
            'buyerAddress' => $isPurchase ? $businessAddress : $partyAddress,
            'sellerPhone' => $isPurchase ? $invoice->party->phone : $business->phone,
        ]);
    }
}
