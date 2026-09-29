<?php

namespace App\Application\Services;

use App\Models\Account;
use App\Models\Business;
use App\Models\Expense;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillLine;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Support\Money;

class BusinessExcelExportService
{
    public function __construct(
        private InventoryService $inventoryService,
        private ExcelExportWriter $excelWriter,
    ) {}

    public function buildWorkbook(Business $business, FinancialYear $financialYear): string
    {
        $path = $this->excelWriter->tempPath('excel_export');

        $sheets = [
            $this->summarySheet($business, $financialYear),
            ['sheetName' => 'Sales Invoices'] + $this->salesInvoiceTable($business, $financialYear),
            ['sheetName' => 'Sales Lines'] + $this->salesInvoiceLineTable($business, $financialYear),
            ['sheetName' => 'Purchase Bills'] + $this->purchaseBillTable($business, $financialYear),
            ['sheetName' => 'Purchase Lines'] + $this->purchaseBillLineTable($business, $financialYear),
            ['sheetName' => 'Parties'] + $this->partyTable($business),
            ['sheetName' => 'Products'] + $this->productTable($business),
            ['sheetName' => 'Payments'] + $this->paymentTable($business, $financialYear),
            ['sheetName' => 'Expenses'] + $this->expenseTable($business, $financialYear),
            ['sheetName' => 'Accounts'] + $this->accountTable($business),
        ];

        return $this->excelWriter->createWorkbook($path, $sheets);
    }

    public function filename(Business $business, FinancialYear $financialYear): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($business->slug));

        return sprintf('%s-fy-%s-export.xlsx', $slug, str_replace('/', '-', $financialYear->label));
    }

    public function cleanup(string $path): void
    {
        $this->excelWriter->cleanup($path);
    }

    /**
     * The summary sheet is a key/value layout rather than a table, so it carries no
     * `columns` and is written without widths, a frozen header or an auto-filter.
     *
     * @return array{sheetName: string, rows: list<list<mixed>>}
     */
    private function summarySheet(Business $business, FinancialYear $financialYear): array
    {
        return [
            'sheetName' => 'Summary',
            'rows' => [
                ['Business Export Summary'],
                [],
                ['Business', $business->name],
                ['Legal name', $business->legal_name ?? ''],
                ['GSTIN', $business->gstin ?? ''],
                ['State', $business->state ?? ''],
                ['Financial year', $financialYear->label],
                ['Exported at', now()->format('Y-m-d H:i:s')],
                [],
                ['Record counts'],
                ['Sales invoices', SalesInvoice::query()->where('business_id', $business->id)->where('financial_year_id', $financialYear->id)->count()],
                ['Purchase bills', PurchaseBill::query()->where('business_id', $business->id)->where('financial_year_id', $financialYear->id)->count()],
                ['Parties', Party::query()->where('business_id', $business->id)->count()],
                ['Products', Product::query()->where('business_id', $business->id)->count()],
                ['Payments', Payment::query()->where('business_id', $business->id)->where('financial_year_id', $financialYear->id)->count()],
                ['Expenses', Expense::query()->where('business_id', $business->id)->where('financial_year_id', $financialYear->id)->count()],
            ],
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function salesInvoiceTable(Business $business, FinancialYear $financialYear): array
    {
        $invoices = SalesInvoice::query()
            ->with('party')
            ->where('business_id', $business->id)
            ->where('financial_year_id', $financialYear->id)
            ->orderBy('invoice_date')
            ->orderBy('invoice_number')
            ->get();

        $rows = [];

        foreach ($invoices as $invoice) {
            $rows[] = [
                'invoice_number' => $invoice->invoice_number,
                'date' => $invoice->invoice_date,
                'party' => $invoice->party->name,
                'billing_type' => $invoice->billing_type->value,
                'status' => $invoice->status->value,
                'settlement' => $invoice->settlement_mode->value,
                'subtotal' => Money::paiseToRupees($invoice->subtotal_amount),
                'tax' => Money::paiseToRupees($invoice->tax_amount),
                'total' => Money::paiseToRupees($invoice->total_amount),
                'reference' => $invoice->reference ?? '',
                'notes' => $invoice->notes ?? '',
            ];
        }

        return [
            'columns' => [
                ['header' => 'Invoice Number', 'key' => 'invoice_number', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Date', 'key' => 'date', 'type' => ExcelExportWriter::TYPE_DATE],
                ['header' => 'Customer', 'key' => 'party', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Billing Type', 'key' => 'billing_type', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Settlement', 'key' => 'settlement', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Subtotal (₹)', 'key' => 'subtotal', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Tax (₹)', 'key' => 'tax', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Total (₹)', 'key' => 'total', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Reference', 'key' => 'reference', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Notes', 'key' => 'notes', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function salesInvoiceLineTable(Business $business, FinancialYear $financialYear): array
    {
        $lines = SalesInvoiceLine::query()
            ->whereHas('salesInvoice', fn ($query) => $query
                ->where('business_id', $business->id)
                ->where('financial_year_id', $financialYear->id))
            ->with('salesInvoice')
            ->orderBy('sales_invoice_id')
            ->orderBy('line_number')
            ->get();

        $rows = [];

        foreach ($lines as $line) {
            $rows[] = [
                'invoice_number' => $line->salesInvoice->invoice_number,
                'line_number' => $line->line_number,
                'description' => $line->description,
                'hsn_sac' => $line->hsn_sac ?? '',
                'quantity' => (float) $line->quantity,
                'unit_price' => Money::paiseToRupees($line->unit_price),
                'tax_rate' => (float) $line->tax_rate,
                'tax_amount' => Money::paiseToRupees($line->tax_amount),
                'line_total' => Money::paiseToRupees($line->line_total),
            ];
        }

        return [
            'columns' => [
                ['header' => 'Invoice Number', 'key' => 'invoice_number', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Line', 'key' => 'line_number', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Description', 'key' => 'description', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'HSN/SAC', 'key' => 'hsn_sac', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Qty', 'key' => 'quantity', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Rate (₹)', 'key' => 'unit_price', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Tax %', 'key' => 'tax_rate', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Tax (₹)', 'key' => 'tax_amount', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Line Total (₹)', 'key' => 'line_total', 'type' => ExcelExportWriter::TYPE_CURRENCY],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function purchaseBillTable(Business $business, FinancialYear $financialYear): array
    {
        $bills = PurchaseBill::query()
            ->with('party')
            ->where('business_id', $business->id)
            ->where('financial_year_id', $financialYear->id)
            ->orderBy('bill_date')
            ->orderBy('bill_number')
            ->get();

        $rows = [];

        foreach ($bills as $bill) {
            $rows[] = [
                'bill_number' => $bill->bill_number,
                'date' => $bill->bill_date,
                'party' => $bill->party->name,
                'billing_type' => $bill->billing_type->value,
                'status' => $bill->status->value,
                'settlement' => $bill->settlement_mode->value,
                'subtotal' => Money::paiseToRupees($bill->subtotal_amount),
                'tax' => Money::paiseToRupees($bill->tax_amount),
                'total' => Money::paiseToRupees($bill->total_amount),
                'reference' => $bill->reference ?? '',
                'notes' => $bill->notes ?? '',
            ];
        }

        return [
            'columns' => [
                ['header' => 'Bill Number', 'key' => 'bill_number', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Date', 'key' => 'date', 'type' => ExcelExportWriter::TYPE_DATE],
                ['header' => 'Supplier', 'key' => 'party', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Billing Type', 'key' => 'billing_type', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Settlement', 'key' => 'settlement', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Subtotal (₹)', 'key' => 'subtotal', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Tax (₹)', 'key' => 'tax', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Total (₹)', 'key' => 'total', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Reference', 'key' => 'reference', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Notes', 'key' => 'notes', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function purchaseBillLineTable(Business $business, FinancialYear $financialYear): array
    {
        $lines = PurchaseBillLine::query()
            ->whereHas('purchaseBill', fn ($query) => $query
                ->where('business_id', $business->id)
                ->where('financial_year_id', $financialYear->id))
            ->with('purchaseBill')
            ->orderBy('purchase_bill_id')
            ->orderBy('line_number')
            ->get();

        $rows = [];

        foreach ($lines as $line) {
            $rows[] = [
                'bill_number' => $line->purchaseBill->bill_number,
                'line_number' => $line->line_number,
                'description' => $line->description,
                'hsn_sac' => $line->hsn_sac ?? '',
                'quantity' => (float) $line->quantity,
                'unit_price' => Money::paiseToRupees($line->unit_price),
                'tax_rate' => (float) $line->tax_rate,
                'tax_amount' => Money::paiseToRupees($line->tax_amount),
                'line_total' => Money::paiseToRupees($line->line_total),
            ];
        }

        return [
            'columns' => [
                ['header' => 'Bill Number', 'key' => 'bill_number', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Line', 'key' => 'line_number', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Description', 'key' => 'description', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'HSN/SAC', 'key' => 'hsn_sac', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Qty', 'key' => 'quantity', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Rate (₹)', 'key' => 'unit_price', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Tax %', 'key' => 'tax_rate', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Tax (₹)', 'key' => 'tax_amount', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Line Total (₹)', 'key' => 'line_total', 'type' => ExcelExportWriter::TYPE_CURRENCY],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function partyTable(Business $business): array
    {
        $parties = Party::query()
            ->where('business_id', $business->id)
            ->orderBy('name')
            ->get();

        $rows = [];

        foreach ($parties as $party) {
            $rows[] = [
                'name' => $party->name,
                'party_type' => $party->party_type->value,
                'gstin' => $party->gstin ?? '',
                'phone' => $party->phone ?? '',
                'email' => $party->email ?? '',
                'city' => $party->billing_city ?? '',
                'state' => $party->billing_state ?? '',
                'opening_balance' => Money::paiseToRupees($party->opening_balance ?? 0),
            ];
        }

        return [
            'columns' => [
                ['header' => 'Name', 'key' => 'name', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Type', 'key' => 'party_type', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'GSTIN', 'key' => 'gstin', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Phone', 'key' => 'phone', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Email', 'key' => 'email', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'City', 'key' => 'city', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'State', 'key' => 'state', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Opening Balance (₹)', 'key' => 'opening_balance', 'type' => ExcelExportWriter::TYPE_CURRENCY],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function productTable(Business $business): array
    {
        $products = Product::query()
            ->with('unit')
            ->where('business_id', $business->id)
            ->orderBy('name')
            ->get();

        $rows = [];

        foreach ($products as $product) {
            $rows[] = [
                'name' => $product->name,
                'sku' => $product->sku ?? '',
                'hsn_sac' => $product->hsn_sac ?? '',
                'unit' => $product->unit?->name ?? '',
                'selling_price' => Money::paiseToRupees($product->selling_price),
                'purchase_price' => Money::paiseToRupees($product->purchase_price),
                'tax_rate' => (float) $product->tax_rate,
                'current_stock' => $this->inventoryService->currentStock($product),
                'minimum_stock' => (float) $product->minimum_stock,
            ];
        }

        return [
            'columns' => [
                ['header' => 'Name', 'key' => 'name', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'SKU', 'key' => 'sku', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'HSN/SAC', 'key' => 'hsn_sac', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Unit', 'key' => 'unit', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Selling Price (₹)', 'key' => 'selling_price', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Purchase Price (₹)', 'key' => 'purchase_price', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Tax %', 'key' => 'tax_rate', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Current Stock', 'key' => 'current_stock', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Min Stock', 'key' => 'minimum_stock', 'type' => ExcelExportWriter::TYPE_NUMBER],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function paymentTable(Business $business, FinancialYear $financialYear): array
    {
        $payments = Payment::query()
            ->with('party')
            ->where('business_id', $business->id)
            ->where('financial_year_id', $financialYear->id)
            ->orderBy('payment_date')
            ->orderBy('payment_number')
            ->get();

        $rows = [];

        foreach ($payments as $payment) {
            $rows[] = [
                'payment_number' => $payment->payment_number,
                'date' => $payment->payment_date,
                'payment_type' => $payment->payment_type->value,
                'party' => $payment->party?->name ?? '',
                'payment_mode' => $payment->payment_mode->value,
                'amount' => Money::paiseToRupees($payment->amount),
                'reference' => $payment->reference ?? '',
                'notes' => $payment->notes ?? '',
            ];
        }

        return [
            'columns' => [
                ['header' => 'Payment Number', 'key' => 'payment_number', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Date', 'key' => 'date', 'type' => ExcelExportWriter::TYPE_DATE],
                ['header' => 'Type', 'key' => 'payment_type', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Party', 'key' => 'party', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Mode', 'key' => 'payment_mode', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Amount (₹)', 'key' => 'amount', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Reference', 'key' => 'reference', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Notes', 'key' => 'notes', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function expenseTable(Business $business, FinancialYear $financialYear): array
    {
        $expenses = Expense::query()
            ->with('expenseAccount')
            ->where('business_id', $business->id)
            ->where('financial_year_id', $financialYear->id)
            ->orderBy('expense_date')
            ->orderBy('expense_number')
            ->get();

        $rows = [];

        foreach ($expenses as $expense) {
            $rows[] = [
                'expense_number' => $expense->expense_number,
                'date' => $expense->expense_date,
                'account' => $expense->expenseAccount?->name ?? '',
                'amount' => Money::paiseToRupees($expense->amount),
                'tax' => Money::paiseToRupees($expense->tax_amount),
                'status' => $expense->status->value,
                'reference' => $expense->reference ?? '',
                'description' => $expense->description ?? '',
            ];
        }

        return [
            'columns' => [
                ['header' => 'Expense Number', 'key' => 'expense_number', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Date', 'key' => 'date', 'type' => ExcelExportWriter::TYPE_DATE],
                ['header' => 'Account', 'key' => 'account', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Amount (₹)', 'key' => 'amount', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Tax (₹)', 'key' => 'tax', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Reference', 'key' => 'reference', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Description', 'key' => 'description', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function accountTable(Business $business): array
    {
        $accounts = Account::query()
            ->where('business_id', $business->id)
            ->orderBy('code')
            ->get();

        $rows = [];

        foreach ($accounts as $account) {
            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
                'status' => $account->status->value,
                'opening_balance_side' => $account->opening_balance_side?->value ?? '',
            ];
        }

        return [
            'columns' => [
                ['header' => 'Code', 'key' => 'code', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Name', 'key' => 'name', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Type', 'key' => 'type', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Balance Side', 'key' => 'opening_balance_side', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }
}
