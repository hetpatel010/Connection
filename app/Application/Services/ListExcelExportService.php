<?php

namespace App\Application\Services;

use App\Domain\Exports\ListExportModule;
use App\Models\Account;
use App\Models\Business;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Expense;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Models\Unit;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;

class ListExcelExportService
{
    public function __construct(
        private ExcelExportWriter $writer,
        private InventoryService $inventoryService,
        private PaymentAllocationService $allocationService,
    ) {}

    /**
     * @param  array{search?: string, type?: string, group?: string, status?: string}  $filters
     */
    public function export(ListExportModule $module, Business $business, array $filters = []): string
    {
        $search = trim($filters['search'] ?? '');
        $type = trim($filters['type'] ?? '');

        $table = match ($module) {
            ListExportModule::SalesInvoices => $this->salesInvoiceTable($search),
            ListExportModule::PurchaseBills => $this->purchaseBillTable($search),
            ListExportModule::Customers => $this->partyTable(Party::query()->customers(), $search),
            ListExportModule::Suppliers => $this->partyTable(Party::query()->suppliers(), $search),
            ListExportModule::Products => $this->productTable($search),
            ListExportModule::Categories => $this->categoryTable(),
            ListExportModule::Units => $this->unitTable(),
            ListExportModule::Payments => $this->paymentTable($search, $type),
            ListExportModule::Expenses => $this->expenseTable($search),
            ListExportModule::CreditNotes => $this->creditNoteTable($search),
            ListExportModule::DebitNotes => $this->debitNoteTable($search),
            ListExportModule::Inventory => $this->inventoryTable($search),
            ListExportModule::Accounts => $this->accountTable($filters),
        };

        $path = $this->writer->tempPath($module->filenamePrefix());

        return $this->writer->createExcel($path, [
            'sheetName' => $module->sheetName(),
            'columns' => $table['columns'],
            'rows' => $table['rows'],
        ]);
    }

    public function filename(ListExportModule $module, Business $business): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($business->slug));
        $date = now()->format('Y-m-d');

        return sprintf('%s-%s-%s.xlsx', $slug, $module->filenamePrefix(), $date);
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function salesInvoiceTable(string $search): array
    {
        $invoices = SalesInvoice::query()
            ->with('party')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('invoice_number', 'like', '%'.$search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('invoice_date')
            ->get();

        $allocatedAmounts = $this->allocationService->allocatedAmountsFor(
            'sales_invoice',
            $invoices->pluck('id')->all(),
        );

        $rows = [];

        foreach ($invoices as $invoice) {
            $allocated = $allocatedAmounts[$invoice->id] ?? 0;
            $outstanding = $invoice->status->isConfirmed() && $invoice->settlement_mode->isCredit()
                ? max(0, $invoice->total_amount - $allocated)
                : 0;

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
                'outstanding' => Money::paiseToRupees($outstanding),
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
                ['header' => 'Outstanding (₹)', 'key' => 'outstanding', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Reference', 'key' => 'reference', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Notes', 'key' => 'notes', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function purchaseBillTable(string $search): array
    {
        $bills = PurchaseBill::query()
            ->with('party')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('bill_number', 'like', '%'.$search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('bill_date')
            ->get();

        $allocatedAmounts = $this->allocationService->allocatedAmountsFor(
            'purchase_bill',
            $bills->pluck('id')->all(),
        );

        $rows = [];

        foreach ($bills as $bill) {
            $allocated = $allocatedAmounts[$bill->id] ?? 0;
            $outstanding = $bill->status->isConfirmed() && $bill->settlement_mode->isCredit()
                ? max(0, $bill->total_amount - $allocated)
                : 0;

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
                'outstanding' => Money::paiseToRupees($outstanding),
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
                ['header' => 'Outstanding (₹)', 'key' => 'outstanding', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Reference', 'key' => 'reference', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Notes', 'key' => 'notes', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function productTable(string $search): array
    {
        $products = Product::query()
            ->with(['category', 'unit'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhere('barcode', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->get();

        $rows = [];

        foreach ($products as $product) {
            $rows[] = [
                'name' => $product->name,
                'sku' => $product->sku ?? '',
                'barcode' => $product->barcode ?? '',
                'category' => $product->category?->name ?? '',
                'unit' => $product->unit?->symbol ?? '',
                'selling_price' => Money::paiseToRupees($product->selling_price),
                'purchase_price' => Money::paiseToRupees($product->purchase_price),
                'tax_rate' => (float) $product->tax_rate,
                'status' => $product->status->value,
            ];
        }

        return [
            'columns' => [
                ['header' => 'Name', 'key' => 'name', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'SKU', 'key' => 'sku', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Barcode', 'key' => 'barcode', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Category', 'key' => 'category', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Unit', 'key' => 'unit', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Selling Price (₹)', 'key' => 'selling_price', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Purchase Price (₹)', 'key' => 'purchase_price', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Tax %', 'key' => 'tax_rate', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function categoryTable(): array
    {
        $categories = ProductCategory::query()->orderBy('name')->get();

        $rows = [];

        foreach ($categories as $category) {
            $rows[] = [
                'name' => $category->name,
                'description' => $category->description ?? '',
                'status' => $category->status->value,
            ];
        }

        return [
            'columns' => [
                ['header' => 'Name', 'key' => 'name', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Description', 'key' => 'description', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function unitTable(): array
    {
        $units = Unit::query()->orderBy('name')->get();

        $rows = [];

        foreach ($units as $unit) {
            $rows[] = [
                'name' => $unit->name,
                'symbol' => $unit->symbol,
                'decimal_places' => $unit->decimal_places,
                'is_default' => $unit->is_default ? 'Yes' : 'No',
                'is_system' => $unit->is_system ? 'Yes' : 'No',
            ];
        }

        return [
            'columns' => [
                ['header' => 'Name', 'key' => 'name', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Symbol', 'key' => 'symbol', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Decimal Places', 'key' => 'decimal_places', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Default', 'key' => 'is_default', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'System', 'key' => 'is_system', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function paymentTable(string $search, string $type): array
    {
        $payments = Payment::query()
            ->with('party')
            ->when($type !== '', fn ($query) => $query->where('payment_type', $type))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('payment_number', 'like', '%'.$search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('payment_date')
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
                'status' => $payment->status->value,
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
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function expenseTable(string $search): array
    {
        $expenses = Expense::query()
            ->with('expenseAccount')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('expense_number', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('expense_date')
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
    private function creditNoteTable(string $search): array
    {
        $creditNotes = CreditNote::query()
            ->with(['party', 'sourceSalesInvoice'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('credit_note_number', 'like', '%'.$search.'%')
                        ->orWhere('reference', 'like', '%'.$search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('credit_note_date')
            ->get();

        $rows = [];

        foreach ($creditNotes as $creditNote) {
            $rows[] = [
                'credit_note_number' => $creditNote->credit_note_number,
                'date' => $creditNote->credit_note_date,
                'party' => $creditNote->party->name,
                'source_invoice' => $creditNote->sourceSalesInvoice?->invoice_number ?? '',
                'status' => $creditNote->status->value,
                'total' => Money::paiseToRupees($creditNote->total_amount),
                'reference' => $creditNote->reference ?? '',
                'notes' => $creditNote->notes ?? '',
            ];
        }

        return [
            'columns' => [
                ['header' => 'Credit Note Number', 'key' => 'credit_note_number', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Date', 'key' => 'date', 'type' => ExcelExportWriter::TYPE_DATE],
                ['header' => 'Customer', 'key' => 'party', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Source Invoice', 'key' => 'source_invoice', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
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
    private function debitNoteTable(string $search): array
    {
        $debitNotes = DebitNote::query()
            ->with(['party', 'sourcePurchaseBill'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder->where('debit_note_number', 'like', '%'.$search.'%')
                        ->orWhere('reference', 'like', '%'.$search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('debit_note_date')
            ->get();

        $rows = [];

        foreach ($debitNotes as $debitNote) {
            $rows[] = [
                'debit_note_number' => $debitNote->debit_note_number,
                'date' => $debitNote->debit_note_date,
                'party' => $debitNote->party->name,
                'source_bill' => $debitNote->sourcePurchaseBill?->bill_number ?? '',
                'status' => $debitNote->status->value,
                'total' => Money::paiseToRupees($debitNote->total_amount),
                'reference' => $debitNote->reference ?? '',
                'notes' => $debitNote->notes ?? '',
            ];
        }

        return [
            'columns' => [
                ['header' => 'Debit Note Number', 'key' => 'debit_note_number', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Date', 'key' => 'date', 'type' => ExcelExportWriter::TYPE_DATE],
                ['header' => 'Supplier', 'key' => 'party', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Source Bill', 'key' => 'source_bill', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
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
    private function inventoryTable(string $search): array
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $levels = $this->inventoryService->stockLevels(
            $business,
            $search !== '' ? $search : null,
        );

        $rows = [];

        foreach ($levels as $level) {
            $product = $level['product'];

            $rows[] = [
                'product' => $product->name,
                'sku' => $product->sku ?? '',
                'unit' => $product->unit?->symbol ?? '',
                'current_stock' => (float) $level['current_stock'],
                'minimum_stock' => (float) $level['minimum_stock'],
                'is_low' => $level['is_low'] ? 'Yes' : 'No',
            ];
        }

        return [
            'columns' => [
                ['header' => 'Product', 'key' => 'product', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'SKU', 'key' => 'sku', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Unit', 'key' => 'unit', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'In Stock', 'key' => 'current_stock', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Minimum Stock', 'key' => 'minimum_stock', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Low Stock', 'key' => 'is_low', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  array{search?: string, type?: string, group?: string, status?: string, view?: string}  $filters
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function accountTable(array $filters): array
    {
        $accounts = Account::query()
            ->with('parent')
            ->filtered($filters)
            ->get();

        $rows = [];

        foreach ($accounts as $account) {
            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
                'group' => $account->group,
                'parent' => $account->parent?->name ?? '',
                'status' => $account->status->value,
            ];
        }

        return [
            'columns' => [
                ['header' => 'Code', 'key' => 'code', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Name', 'key' => 'name', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Type', 'key' => 'type', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Group', 'key' => 'group', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Parent Account', 'key' => 'parent', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  Builder<Party>  $query
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function partyTable($query, string $search): array
    {
        $parties = $query
            ->when($search !== '', function ($builder) use ($search): void {
                $builder->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('gstin', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->get();

        $rows = [];

        foreach ($parties as $party) {
            $rows[] = [
                'name' => $party->name,
                'phone' => $party->phone ?? '',
                'email' => $party->email ?? '',
                'gstin' => $party->gstin ?? '',
                'city' => $party->billing_city ?? '',
                'state' => $party->billing_state ?? '',
                'credit_limit' => Money::paiseToRupees($party->credit_limit ?? 0),
                'status' => $party->status->value,
            ];
        }

        return [
            'columns' => [
                ['header' => 'Name', 'key' => 'name', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Phone', 'key' => 'phone', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Email', 'key' => 'email', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'GSTIN', 'key' => 'gstin', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'City', 'key' => 'city', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'State', 'key' => 'state', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Credit Limit (₹)', 'key' => 'credit_limit', 'type' => ExcelExportWriter::TYPE_CURRENCY],
                ['header' => 'Status', 'key' => 'status', 'type' => ExcelExportWriter::TYPE_TEXT],
            ],
            'rows' => $rows,
        ];
    }
}
