<?php

use App\Application\Services\InvoiceDocumentService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Transactions\Enums\BillingType;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Support\GstTaxBreakdown;
use App\Support\IndianAmountInWords;
use App\Support\Money;

test('indian amount in words converts rupee totals', function () {
    expect(IndianAmountInWords::fromPaise(Money::rupeesToPaise(1180)))
        ->toContain('One Thousand')
        ->toContain('Only');
});

test('gst tax breakdown includes hsn summary and place of supply', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create([
        'billing_state' => 'Maharashtra',
        'gstin' => '27AABCU9603R1ZM',
    ]);

    $invoice = app(SalesInvoiceService::class)->createDraft(
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
            ['description' => 'Widget', 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 18, 'hsn_sac' => '8471'],
        ],
    );

    $breakdown = GstTaxBreakdown::forInvoice($invoice, $business);

    expect($breakdown['place_of_supply'])->toContain('Maharashtra');
    expect($breakdown['hsn_summary'])->toHaveCount(1);
    expect($breakdown['hsn_summary'][0]['hsn'])->toBe('8471');
});

test('gst invoice templates use distinct layout markers', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create([
        'billing_state' => $business->state,
        'gstin' => '24AABCU9603R1ZM',
    ]);

    $draftAttributes = [
        'party_id' => $customer->id,
        'invoice_date' => now()->toDateString(),
        'settlement_mode' => SettlementMode::Credit,
        'billing_type' => BillingType::Gst,
    ];

    $line = [
        ['description' => 'GST item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18, 'hsn_sac' => '998314'],
    ];

    $classic = app(InvoiceDocumentService::class)->render(
        tap(app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $user,
            attributes: array_merge($draftAttributes, ['document_template' => 'classic-gst']),
            lines: $line,
        ), $user), fn ($invoice) => $invoice->document_template = 'classic-gst'),
        $business,
    );

    $minimal = app(InvoiceDocumentService::class)->render(
        tap(app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $user,
            attributes: array_merge($draftAttributes, ['document_template' => 'minimal-gst']),
            lines: $line,
        ), $user), fn ($invoice) => $invoice->document_template = 'minimal-gst'),
        $business,
    );

    $modern = app(InvoiceDocumentService::class)->render(
        tap(app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $user,
            attributes: array_merge($draftAttributes, ['document_template' => 'modern-gst']),
            lines: $line,
        ), $user), fn ($invoice) => $invoice->document_template = 'modern-gst'),
        $business,
    );

    expect($classic)->toContain('<table class="invoice-sheet classic">');
    expect($minimal)->toContain('<div class="gst-minimal">');
    expect($modern)->toContain('<div class="gst-modern">');
    expect($classic)->not->toContain('<div class="gst-minimal">');
    expect($minimal)->not->toContain('<table class="invoice-sheet classic">');
});

test('gst invoice template renders indian tax invoice sections', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create([
        'billing_state' => $business->state,
        'gstin' => '24AABCU9603R1ZM',
    ]);

    $invoice = app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
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
            ['description' => 'GST item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18, 'hsn_sac' => '998314'],
        ],
    ), $user);

    $html = app(InvoiceDocumentService::class)->render($invoice, $business);

    expect($html)
        ->toContain('Place of Supply')
        ->toContain('HSN/SAC')
        ->toContain('Amount Chargeable (in words)')
        ->toContain('CGST')
        ->toContain('SGST')
        ->toContain('Authorised Signatory');
});
