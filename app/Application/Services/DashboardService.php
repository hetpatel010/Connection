<?php

namespace App\Application\Services;

use App\Domain\Transactions\Enums\DocumentStatus;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\SalesInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private PartyLedgerService $partyLedgerService,
        private LedgerService $ledgerService,
        private AccountLookupService $accounts,
        private InventoryService $inventoryService,
        private GstReportService $gstReportService,
    ) {}

    /**
     * @return array{
     *     todays_sales: int,
     *     monthly_sales: int,
     *     gst_collected_month: int,
     *     low_stock_count: int,
     *     receivables: int,
     *     payables: int,
     *     cash_and_bank: int,
     *     recent_invoices: Collection<int, SalesInvoice>,
     *     setup: array{has_gstin: bool, has_customers: bool, has_products: bool, has_invoices: bool, is_complete: bool}
     * }
     */
    public function metrics(Business $business, FinancialYear $financialYear, ?Carbon $asOf = null): array
    {
        $asOf ??= now();
        $monthStart = $asOf->copy()->startOfMonth();

        $receivableAgeing = $this->partyLedgerService->ageingReport($business, receivable: true, asOf: $asOf);
        $payableAgeing = $this->partyLedgerService->ageingReport($business, receivable: false, asOf: $asOf);

        $cashAccount = $this->accounts->findByCode($business, '1100');
        $bankAccount = $this->accounts->findByCode($business, '1200');

        $cashBalance = $cashAccount !== null
            ? max(0, $this->ledgerService->accountBalance($cashAccount, $financialYear, $asOf))
            : 0;
        $bankBalance = $bankAccount !== null
            ? max(0, $this->ledgerService->accountBalance($bankAccount, $financialYear, $asOf))
            : 0;

        $recentInvoices = SalesInvoice::query()
            ->with('party')
            ->where('status', DocumentStatus::Confirmed)
            ->orderByDesc('invoice_date')
            ->orderByDesc('invoice_number')
            ->limit(5)
            ->get();

        $gstSummary = $this->gstReportService->generate($business, $monthStart, $asOf);

        $hasCustomers = Party::query()->customers()->exists();
        $hasProducts = Product::query()->exists();
        $hasInvoices = SalesInvoice::query()
            ->where('status', DocumentStatus::Confirmed)
            ->exists();

        $setup = [
            'has_gstin' => filled($business->gstin),
            'has_customers' => $hasCustomers,
            'has_products' => $hasProducts,
            'has_invoices' => $hasInvoices,
            'is_complete' => filled($business->gstin) && $hasCustomers && $hasProducts && $hasInvoices,
        ];

        return [
            'todays_sales' => (int) SalesInvoice::query()
                ->where('status', DocumentStatus::Confirmed)
                ->whereDate('invoice_date', $asOf->toDateString())
                ->sum('total_amount'),
            'monthly_sales' => (int) SalesInvoice::query()
                ->where('status', DocumentStatus::Confirmed)
                ->whereDate('invoice_date', '>=', $monthStart)
                ->whereDate('invoice_date', '<=', $asOf)
                ->sum('total_amount'),
            'gst_collected_month' => $gstSummary['output_tax'],
            'low_stock_count' => $this->inventoryService->lowStockCount($business),
            'receivables' => $receivableAgeing['totals']['total_outstanding'],
            'payables' => $payableAgeing['totals']['total_outstanding'],
            'cash_and_bank' => $cashBalance + $bankBalance,
            'recent_invoices' => $recentInvoices,
            'setup' => $setup,
        ];
    }
}
