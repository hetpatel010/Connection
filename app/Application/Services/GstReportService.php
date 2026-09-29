<?php

namespace App\Application\Services;

use App\Domain\Transactions\Enums\DocumentStatus;
use App\Models\Business;
use App\Models\Expense;
use App\Models\PurchaseBillLine;
use App\Models\SalesInvoiceLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GstReportService
{
    /**
     * @return array{
     *     output_tax: int,
     *     input_tax: int,
     *     net_payable: int,
     *     output_by_rate: Collection<int, array{tax_rate: float, taxable_amount: int, tax_amount: int}>,
     *     input_by_rate: Collection<int, array{tax_rate: float, taxable_amount: int, tax_amount: int}>,
     *     from: string,
     *     to: string
     * }
     */
    public function generate(Business $business, Carbon $from, Carbon $to): array
    {
        $outputByRate = SalesInvoiceLine::query()
            ->where('business_id', $business->id)
            ->whereHas('salesInvoice', fn ($query) => $query
                ->where('status', DocumentStatus::Confirmed)
                ->whereDate('invoice_date', '>=', $from)
                ->whereDate('invoice_date', '<=', $to))
            ->select([
                'tax_rate',
                DB::raw('SUM(ROUND(quantity * unit_price)) as taxable_amount'),
                DB::raw('SUM(tax_amount) as tax_amount'),
            ])
            ->groupBy('tax_rate')
            ->orderBy('tax_rate')
            ->get()
            ->map(fn ($row) => [
                'tax_rate' => (float) $row->tax_rate,
                'taxable_amount' => (int) $row->taxable_amount,
                'tax_amount' => (int) $row->tax_amount,
            ]);

        $inputFromPurchases = PurchaseBillLine::query()
            ->where('business_id', $business->id)
            ->whereHas('purchaseBill', fn ($query) => $query
                ->where('status', DocumentStatus::Confirmed)
                ->whereDate('bill_date', '>=', $from)
                ->whereDate('bill_date', '<=', $to))
            ->select([
                'tax_rate',
                DB::raw('SUM(ROUND(quantity * unit_price)) as taxable_amount'),
                DB::raw('SUM(tax_amount) as tax_amount'),
            ])
            ->groupBy('tax_rate')
            ->orderBy('tax_rate')
            ->get()
            ->map(fn ($row) => [
                'tax_rate' => (float) $row->tax_rate,
                'taxable_amount' => (int) $row->taxable_amount,
                'tax_amount' => (int) $row->tax_amount,
            ]);

        $expenseInputTax = (int) Expense::query()
            ->where('business_id', $business->id)
            ->where('status', DocumentStatus::Confirmed)
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->sum('tax_amount');

        $outputTax = (int) $outputByRate->sum('tax_amount');
        $inputTax = (int) $inputFromPurchases->sum('tax_amount') + $expenseInputTax;

        if ($expenseInputTax > 0) {
            $inputFromPurchases = $inputFromPurchases->push([
                'tax_rate' => 0.0,
                'taxable_amount' => 0,
                'tax_amount' => $expenseInputTax,
            ]);
        }

        return [
            'output_tax' => $outputTax,
            'input_tax' => $inputTax,
            'net_payable' => $outputTax - $inputTax,
            'output_by_rate' => $outputByRate->values(),
            'input_by_rate' => $inputFromPurchases->values(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }
}
