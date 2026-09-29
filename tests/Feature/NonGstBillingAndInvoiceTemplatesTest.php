<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\Gstr1ExportService;
use App\Application\Services\InvoiceDocumentService;
use App\Application\Services\InvoiceTemplateService;
use App\Application\Services\PurchaseBillService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Invoicing\InvoiceTemplateRegistry;
use App\Domain\Transactions\Enums\BillingType;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\SalesInvoice;
use App\Support\Money;
use Illuminate\Support\Carbon;

test('non-gst sales invoice stores zero tax even when lines include tax rate', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
            'billing_type' => BillingType::NonGst,
        ],
        lines: [
            ['description' => 'Consulting', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
        ],
    );

    expect($invoice->billing_type)->toBe(BillingType::NonGst);
    expect($invoice->tax_amount)->toBe(0);
    expect($invoice->total_amount)->toBe(Money::rupeesToPaise(1000));
    expect($invoice->lines->first()->tax_rate)->toBe('0.00');
});

test('non-gst invoices are excluded from gstr1 export', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create(['gstin' => '27AABCU9603R1ZM']);

    $service = app(SalesInvoiceService::class);

    $service->confirm($service->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
            'billing_type' => BillingType::Gst,
        ],
        lines: [
            ['description' => 'GST item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
        ],
    ), $user);

    $service->confirm($service->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
            'billing_type' => BillingType::NonGst,
        ],
        lines: [
            ['description' => 'Non-GST item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 18],
        ],
    ), $user);

    $export = app(Gstr1ExportService::class)->export(
        $business,
        Carbon::parse($financialYear->starts_on),
        now(),
    );

    expect($export['summary']['invoice_count'])->toBe(1);
    expect($export['b2b'])->toHaveCount(1);
});

test('invoice template service resolves business defaults and per-invoice overrides', function () {
    ['business' => $business] = $this->createUserWithBusiness();

    $templateService = app(InvoiceTemplateService::class);

    expect($templateService->defaultTemplate($business, BillingType::Gst))->toBe(InvoiceTemplateRegistry::CLASSIC_GST);
    expect($templateService->defaultTemplate($business, BillingType::NonGst))->toBe(InvoiceTemplateRegistry::CLASSIC_BILL);

    $templateService->setDefaultTemplate($business, BillingType::Gst, InvoiceTemplateRegistry::MINIMAL_GST);
    $templateService->setDefaultTemplate($business, BillingType::NonGst, InvoiceTemplateRegistry::MINIMAL_BILL);

    $business = $business->fresh();

    $invoice = new SalesInvoice([
        'billing_type' => BillingType::Gst,
        'document_template' => InvoiceTemplateRegistry::MODERN_GST,
    ]);

    expect($templateService->resolveForInvoice($invoice, $business))->toBe(InvoiceTemplateRegistry::MODERN_GST);

    $invoice->document_template = null;

    expect($templateService->resolveForInvoice($invoice, $business))->toBe(InvoiceTemplateRegistry::MINIMAL_GST);
});

test('invoice document renders selected non-gst template view', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $invoice = app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
            'billing_type' => BillingType::NonGst,
            'document_template' => InvoiceTemplateRegistry::MINIMAL_BILL,
        ],
        lines: [
            ['description' => 'Service fee', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(2500), 'tax_rate' => 0],
        ],
    ), $user);

    $html = app(InvoiceDocumentService::class)->render($invoice, $business);

    expect($html)->toContain('Non-GST bill');
    expect($html)->not->toContain('CGST');
    expect($html)->toContain('<div class="bill-minimal">');
});

test('non-gst bill templates use distinct styled layouts', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $line = [
        ['description' => 'Service fee', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(2500), 'tax_rate' => 0],
    ];

    $attributes = [
        'party_id' => $customer->id,
        'invoice_date' => now()->toDateString(),
        'settlement_mode' => SettlementMode::Credit,
        'billing_type' => BillingType::NonGst,
    ];

    $classic = app(InvoiceDocumentService::class)->render(
        tap(app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $user,
            attributes: array_merge($attributes, ['document_template' => InvoiceTemplateRegistry::CLASSIC_BILL]),
            lines: $line,
        ), $user), fn ($invoice) => $invoice->document_template = InvoiceTemplateRegistry::CLASSIC_BILL),
        $business,
    );

    $minimal = app(InvoiceDocumentService::class)->render(
        tap(app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $user,
            attributes: array_merge($attributes, ['document_template' => InvoiceTemplateRegistry::MINIMAL_BILL]),
            lines: $line,
        ), $user), fn ($invoice) => $invoice->document_template = InvoiceTemplateRegistry::MINIMAL_BILL),
        $business,
    );

    expect($classic)->toContain('<div class="bill-classic">');
    expect($classic)->toContain('bill-items');
    expect($minimal)->toContain('<div class="bill-minimal">');
    expect($minimal)->toContain('bill-min-items');
    expect($classic)->not->toContain('<div class="bill-minimal">');
});

test('invoice templates settings shows custom template request for sveo', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('settings.invoice-templates'))
        ->assertOk()
        ->assertSee(__('Need a custom invoice template?'), false)
        ->assertSee(branding('vendor.name'), false)
        ->assertSee(__('Request custom template'), false);
});

test('non-gst purchase bill stores zero tax', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    $bill = app(PurchaseBillService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $supplier->id,
            'bill_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
            'billing_type' => BillingType::NonGst,
        ],
        lines: [
            ['description' => 'Office supplies', 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(200), 'tax_rate' => 12],
        ],
    );

    expect($bill->billing_type)->toBe(BillingType::NonGst);
    expect($bill->tax_amount)->toBe(0);
    expect($bill->total_amount)->toBe(Money::rupeesToPaise(400));
});
