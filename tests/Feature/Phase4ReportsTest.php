<?php

use App\Application\Services\BalanceSheetService;
use App\Application\Services\GstReportService;
use App\Application\Services\ProfitAndLossService;
use App\Application\Services\PurchaseBillService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;

test('profit and loss reflects confirmed sales income', function () {
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
        ],
        lines: [
            ['description' => 'Consulting', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(10000), 'tax_rate' => 0],
        ],
    );

    app(SalesInvoiceService::class)->confirm($invoice, $user);

    $report = app(ProfitAndLossService::class)->generate(
        $business,
        $financialYear,
        Carbon::parse($financialYear->starts_on),
        now(),
    );

    expect($report['total_income'])->toBe(Money::rupeesToPaise(10000));
    expect($report['net_profit'])->toBe(Money::rupeesToPaise(10000));
});

test('gst summary aggregates output and input tax', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    $salesInvoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Product A', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
        ],
    );
    app(SalesInvoiceService::class)->confirm($salesInvoice, $user);

    $purchaseBill = app(PurchaseBillService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $supplier->id,
            'bill_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Supplies', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 5],
        ],
    );
    app(PurchaseBillService::class)->confirm($purchaseBill, $user);

    $report = app(GstReportService::class)->generate(
        $business,
        Carbon::parse($financialYear->starts_on),
        now(),
    );

    expect($report['output_tax'])->toBe(Money::rupeesToPaise(180));
    expect($report['input_tax'])->toBe(Money::rupeesToPaise(25));
    expect($report['net_payable'])->toBe(Money::rupeesToPaise(155));
});

test('balance sheet includes current period profit in equity', function () {
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
        ],
        lines: [
            ['description' => 'Service', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(5000), 'tax_rate' => 0],
        ],
    );
    app(SalesInvoiceService::class)->confirm($invoice, $user);

    $report = app(BalanceSheetService::class)->generate(
        $business,
        $financialYear,
        now(),
    );

    expect($report['current_period_profit'])->toBe(Money::rupeesToPaise(5000));
    expect($report['total_assets'])->toBe($report['total_liabilities_and_equity']);
});

test('report pages require appropriate permissions', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('accounting.profit-loss'))
        ->assertOk();

    $this->actingAs($owner)
        ->get(route('accounting.gst-summary'))
        ->assertOk();

    $salesperson = User::factory()->create(['email' => 'sales@test.com']);
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('accounting.profit-loss'))
        ->assertForbidden();
});

test('confirmed invoice can be printed', function () {
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
        ],
        lines: [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);

    $this->actingAs($user)
        ->get(route('sales.invoices.print', $invoice))
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee($customer->name);
});

test('draft invoice print route returns not found', function () {
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
        ],
        lines: [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    );

    expect($invoice->status)->toBe(DocumentStatus::Draft);

    $this->actingAs($user)
        ->get(route('sales.invoices.print', $invoice))
        ->assertNotFound();
});
