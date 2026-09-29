<?php

use App\Application\Services\InvoiceDocumentService;
use App\Application\Services\PurchaseBillDocumentService;
use App\Domain\Invoicing\InvoiceTemplateRegistry;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Http\Controllers\Accounting\AccountController;
use App\Http\Controllers\BusinessExcelExportController;
use App\Http\Controllers\BusinessExportController;
use App\Http\Controllers\Gstr1ExportController;
use App\Http\Controllers\InventoryLedgerExportController;
use App\Http\Controllers\InvoiceDownloadController;
use App\Http\Controllers\ListExcelExportController;
use App\Http\Controllers\Masters\CustomerController;
use App\Http\Controllers\Masters\ProductController;
use App\Http\Controllers\Masters\SupplierController;
use App\Http\Controllers\PurchaseBillDownloadController;
use App\Http\Controllers\VendorContactController;
use App\Models\Account;
use App\Models\Party;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('onboarding/business', 'pages.onboarding.business')
        ->name('onboarding.business');

    Route::view('dashboard', 'dashboard')
        ->middleware(['business', 'permission:view_dashboard'])
        ->name('dashboard');

    Route::view('profile', 'profile')
        ->middleware('business')
        ->name('profile');

    Route::view('about', 'about')
        ->middleware('business')
        ->name('about');

    Route::view('businesses/create', 'businesses.create')
        ->name('businesses.create');

    Route::get('exports/{module}', ListExcelExportController::class)
        ->middleware('business')
        ->name('exports.list-excel');

    Route::middleware('business')->prefix('accounting')->name('accounting.')->group(function () {
        Route::view('accounts', 'accounting.accounts')
            ->middleware('permission:manage_chart_of_accounts')
            ->name('accounts.index');

        Route::view('accounts/create', 'accounting.accounts.create')
            ->middleware('permission:manage_chart_of_accounts')
            ->name('accounts.create');

        Route::get('accounts/{account}', fn (Account $account) => view('accounting.accounts.show', compact('account')))
            ->middleware('permission:manage_chart_of_accounts')
            ->name('accounts.show');

        Route::get('accounts/{account}/edit', fn (Account $account) => view('accounting.accounts.edit', compact('account')))
            ->middleware('permission:manage_chart_of_accounts')
            ->name('accounts.edit');

        Route::delete('accounts/{account}', [AccountController::class, 'destroy'])
            ->middleware('permission:manage_chart_of_accounts')
            ->name('accounts.destroy');

        Route::view('journals/create', 'accounting.journals.create')
            ->middleware('permission:create_journal')
            ->name('journals.create');

        Route::view('ledger', 'accounting.ledger')
            ->middleware('permission:view_ledger')
            ->name('ledger');

        Route::view('bank-reconciliation', 'accounting.bank-reconciliation')
            ->middleware('permission:view_ledger')
            ->name('bank-reconciliation');

        Route::view('trial-balance', 'accounting.trial-balance')
            ->middleware('permission:view_trial_balance')
            ->name('trial-balance');

        Route::view('profit-loss', 'accounting.profit-loss')
            ->middleware('permission:view_profit_loss')
            ->name('profit-loss');

        Route::view('balance-sheet', 'accounting.balance-sheet')
            ->middleware('permission:view_balance_sheet')
            ->name('balance-sheet');

        Route::view('gst-summary', 'accounting.gst-summary')
            ->middleware('permission:manage_gst')
            ->name('gst-summary');

        Route::get('gst-summary/gstr1-export', Gstr1ExportController::class)
            ->middleware('permission:manage_gst')
            ->name('gst-summary.gstr1-export');
    });

    Route::middleware('business')->prefix('settings')->name('settings.')->group(function () {
        Route::view('business', 'settings.business')
            ->middleware('permission:manage_settings')
            ->name('business');

        Route::view('users', 'settings.users')
            ->middleware('permission:manage_users')
            ->name('users');

        Route::view('roles', 'settings.roles')
            ->middleware('permission:manage_roles')
            ->name('roles');

        Route::view('financial-years', 'settings.financial-years')
            ->middleware('permission:close_financial_year')
            ->name('financial-years');

        Route::view('export', 'settings.export')
            ->middleware('permission:export_data')
            ->name('export.index');

        Route::get('export/excel', BusinessExcelExportController::class)
            ->middleware('permission:export_data')
            ->name('export.excel');

        Route::get('export/backup', BusinessExportController::class)
            ->middleware('permission:export_data')
            ->name('export.backup');

        Route::view('invoice-templates', 'settings.invoice-templates')
            ->middleware('permission:manage_settings')
            ->name('invoice-templates');

        Route::get('invoice-templates/contact', [VendorContactController::class, 'create'])
            ->middleware('permission:manage_settings')
            ->name('invoice-templates.contact');

        Route::post('invoice-templates/contact', VendorContactController::class)
            ->middleware('permission:manage_settings')
            ->name('invoice-templates.contact.store');

        Route::get('invoice-templates/preview/{template}', function (string $template, InvoiceDocumentService $invoiceDocumentService) {
            abort_unless(in_array($template, InvoiceTemplateRegistry::slugs(), true), 404);

            $business = business_context()->business();
            abort_if($business === null, 404);

            $billingType = InvoiceTemplateRegistry::billingTypeFor($template);
            $customer = Party::query()->customers()->orderBy('name')->first();
            abort_if($customer === null, 404);

            $invoice = new SalesInvoice([
                'invoice_number' => 'PREVIEW-001',
                'invoice_date' => now(),
                'settlement_mode' => SettlementMode::Credit,
                'subtotal_amount' => 100000,
                'tax_amount' => $billingType->isGst() ? 18000 : 0,
                'total_amount' => $billingType->isGst() ? 118000 : 100000,
                'billing_type' => $billingType,
                'document_template' => $template,
                'notes' => __('Sample preview — not a real invoice.'),
            ]);
            $invoice->setRelation('party', $customer);
            $invoice->setRelation('lines', collect([
                new SalesInvoiceLine([
                    'description' => __('Sample product or service'),
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'tax_rate' => $billingType->isGst() ? 18 : 0,
                    'tax_amount' => $billingType->isGst() ? 18000 : 0,
                    'line_total' => $billingType->isGst() ? 118000 : 100000,
                    'hsn_sac' => $billingType->isGst() ? '998314' : null,
                ]),
            ]));

            return view('sales.invoices.document', $invoiceDocumentService->viewData($invoice, $business));
        })->middleware('permission:manage_settings')->name('invoice-templates.preview');
    });

    Route::middleware('business')->prefix('reports')->name('reports.')->group(function () {
        Route::view('receivable-ageing', 'reports.receivable-ageing')
            ->middleware('permission:view_invoice')
            ->name('receivable-ageing');

        Route::view('payable-ageing', 'reports.payable-ageing')
            ->middleware('permission:view_purchase')
            ->name('payable-ageing');

        Route::view('party-statement', 'reports.party-statement')
            ->middleware('permission:view_payment')
            ->name('party-statement');
    });

    Route::middleware('business')->prefix('masters')->name('masters.')->group(function () {
        Route::view('customers', 'masters.customers.index')
            ->middleware('permission:view_customers')
            ->name('customers.index');

        Route::view('customers/create', 'masters.customers.create')
            ->middleware('permission:manage_customers')
            ->name('customers.create');

        Route::get('customers/{party}/edit', function (Party $party) {
            abort_unless($party->party_type->isCustomer(), 404);

            return view('masters.customers.edit', compact('party'));
        })->middleware('permission:manage_customers')->name('customers.edit');

        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])
            ->middleware('permission:manage_customers')
            ->name('customers.destroy');

        Route::view('suppliers', 'masters.suppliers.index')
            ->middleware('permission:view_suppliers')
            ->name('suppliers.index');

        Route::view('suppliers/create', 'masters.suppliers.create')
            ->middleware('permission:manage_suppliers')
            ->name('suppliers.create');

        Route::get('suppliers/{party}/edit', function (Party $party) {
            abort_unless($party->party_type->isSupplier(), 404);

            return view('masters.suppliers.edit', compact('party'));
        })->middleware('permission:manage_suppliers')->name('suppliers.edit');

        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])
            ->middleware('permission:manage_suppliers')
            ->name('suppliers.destroy');

        Route::view('products', 'masters.products.index')
            ->middleware('permission:view_products')
            ->name('products.index');

        Route::view('products/create', 'masters.products.create')
            ->middleware('permission:manage_products')
            ->name('products.create');

        Route::get('products/{product}/edit', fn (Product $product) => view('masters.products.edit', compact('product')))
            ->middleware('permission:manage_products')
            ->name('products.edit');

        Route::delete('products/{product}', [ProductController::class, 'destroy'])
            ->middleware('permission:manage_products')
            ->name('products.destroy');

        Route::view('categories', 'masters.categories.index')
            ->middleware('permission:manage_categories')
            ->name('categories.index');

        Route::view('units', 'masters.units.index')
            ->middleware('permission:manage_units')
            ->name('units.index');
    });

    Route::middleware('business')->prefix('sales')->name('sales.')->group(function () {
        Route::view('invoices', 'sales.invoices.index')
            ->middleware('permission:view_invoice')
            ->name('invoices.index');

        Route::view('invoices/create', 'sales.invoices.create')
            ->middleware('permission:create_invoice')
            ->name('invoices.create');

        Route::get('invoices/{invoice}/edit', fn (SalesInvoice $invoice) => view('sales.invoices.edit', compact('invoice')))
            ->middleware('permission:edit_invoice')
            ->name('invoices.edit');

        Route::get('invoices/{invoice}/print', function (SalesInvoice $invoice, InvoiceDocumentService $invoiceDocumentService) {
            abort_unless($invoice->status->isConfirmed(), 404);

            $business = business_context()->business();
            abort_if($business === null, 404);

            return view('sales.invoices.document', $invoiceDocumentService->viewData($invoice, $business, showToolbar: true));
        })->middleware('permission:view_invoice')->name('invoices.print');

        Route::get('invoices/{invoice}/download', InvoiceDownloadController::class)
            ->middleware('permission:view_invoice')
            ->name('invoices.download');

        Route::view('credit-notes', 'sales.credit-notes.index')
            ->middleware('permission:view_invoice')
            ->name('credit-notes.index');
    });

    Route::middleware('business')->prefix('purchases')->name('purchases.')->group(function () {
        Route::view('bills', 'purchases.bills.index')
            ->middleware('permission:view_purchase')
            ->name('bills.index');

        Route::view('bills/create', 'purchases.bills.create')
            ->middleware('permission:create_purchase')
            ->name('bills.create');

        Route::get('bills/{bill}/edit', fn (PurchaseBill $bill) => view('purchases.bills.edit', compact('bill')))
            ->middleware('permission:edit_purchase')
            ->name('bills.edit');

        Route::get('bills/{bill}/print', function (PurchaseBill $bill, PurchaseBillDocumentService $purchaseBillDocumentService) {
            abort_unless($bill->status->isConfirmed(), 404);

            $business = business_context()->business();
            abort_if($business === null, 404);

            return view('sales.invoices.document', $purchaseBillDocumentService->viewData($bill, $business, showToolbar: true));
        })->middleware('permission:view_purchase')->name('bills.print');

        Route::get('bills/{bill}/download', PurchaseBillDownloadController::class)
            ->middleware('permission:view_purchase')
            ->name('bills.download');

        Route::view('debit-notes', 'purchases.debit-notes.index')
            ->middleware('permission:view_purchase')
            ->name('debit-notes.index');
    });

    Route::middleware('business')->prefix('payments')->name('payments.')->group(function () {
        Route::view('/', 'payments.index')
            ->middleware('permission:view_payment')
            ->name('index');

        Route::view('in/create', 'payments.in.create')
            ->middleware('permission:create_payment')
            ->name('in.create');

        Route::view('out/create', 'payments.out.create')
            ->middleware('permission:create_payment')
            ->name('out.create');
    });

    Route::middleware('business')->prefix('expenses')->name('expenses.')->group(function () {
        Route::view('/', 'expenses.index')
            ->middleware('permission:view_expense')
            ->name('index');

        Route::view('create', 'expenses.create')
            ->middleware('permission:create_expense')
            ->name('create');
    });

    Route::middleware('business')->prefix('inventory')->name('inventory.')->group(function () {
        Route::view('/', 'inventory.index')
            ->middleware('permission:manage_inventory')
            ->name('index');

        Route::get('{product}/ledger/export', InventoryLedgerExportController::class)
            ->middleware('permission:manage_inventory')
            ->name('ledger.export');

        Route::get('{product}/ledger', fn (Product $product) => view('inventory.ledger', compact('product')))
            ->middleware('permission:manage_inventory')
            ->name('ledger');

        Route::get('{product}/adjust', fn (Product $product) => view('inventory.adjust', compact('product')))
            ->middleware('permission:manage_inventory')
            ->name('adjust');
    });
});

require __DIR__.'/auth.php';
