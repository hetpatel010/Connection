<?php

namespace App\Application\Services;

use App\Domain\Transactions\Enums\BillingType;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Models\Business;
use App\Models\SalesInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Gstr1ExportService
{
    /**
     * @return array{
     *     version: string,
     *     business: array{gstin: string|null, legal_name: string|null, state: string|null},
     *     period: array{from: string, to: string},
     *     summary: array{invoice_count: int, taxable_value: int, tax_amount: int, total_value: int},
     *     b2b: Collection<int, array<string, mixed>>,
     *     b2c: Collection<int, array<string, mixed>>
     * }
     */
    public function export(Business $business, Carbon $from, Carbon $to): array
    {
        $invoices = SalesInvoice::query()
            ->with(['party', 'lines'])
            ->where('status', DocumentStatus::Confirmed)
            ->where('billing_type', BillingType::Gst)
            ->whereDate('invoice_date', '>=', $from)
            ->whereDate('invoice_date', '<=', $to)
            ->orderBy('invoice_date')
            ->orderBy('invoice_number')
            ->get();

        $b2b = collect();
        $b2c = collect();

        foreach ($invoices as $invoice) {
            $payload = [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date->toDateString(),
                'customer_name' => $invoice->party->name,
                'customer_gstin' => $invoice->party->gstin,
                'place_of_supply' => $invoice->party->billing_state ?? $business->state,
                'taxable_value' => $invoice->subtotal_amount,
                'tax_amount' => $invoice->tax_amount,
                'invoice_value' => $invoice->total_amount,
                'lines' => $invoice->lines->map(fn ($line): array => [
                    'description' => $line->description,
                    'hsn_sac' => $line->hsn_sac,
                    'quantity' => (float) $line->quantity,
                    'taxable_value' => (int) round((float) $line->quantity * $line->unit_price),
                    'tax_rate' => (float) $line->tax_rate,
                    'tax_amount' => $line->tax_amount,
                    'total' => $line->line_total,
                ])->values()->all(),
            ];

            if ($invoice->party->gstin) {
                $b2b->push($payload);
            } else {
                $b2c->push($payload);
            }
        }

        return [
            'version' => '1.0',
            'business' => [
                'gstin' => $business->gstin,
                'legal_name' => $business->legal_name ?? $business->name,
                'state' => $business->state,
            ],
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'summary' => [
                'invoice_count' => $invoices->count(),
                'taxable_value' => (int) $invoices->sum('subtotal_amount'),
                'tax_amount' => (int) $invoices->sum('tax_amount'),
                'total_value' => (int) $invoices->sum('total_amount'),
            ],
            'b2b' => $b2b->values(),
            'b2c' => $b2c->values(),
        ];
    }

    public function filename(Carbon $from, Carbon $to): string
    {
        return sprintf('gstr1-%s-to-%s.json', $from->format('Y-m-d'), $to->format('Y-m-d'));
    }

    public function toJson(Business $business, Carbon $from, Carbon $to): string
    {
        return json_encode($this->export($business, $from, $to), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
