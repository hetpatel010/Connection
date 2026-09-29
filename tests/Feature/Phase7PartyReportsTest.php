<?php

use App\Application\Services\PartyLedgerService;
use App\Application\Services\PaymentService;
use App\Application\Services\PurchaseBillService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;

test('party balance reflects invoices minus payments for customers', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $cashAccount = $this->accountByCode($business, '1100');
    $ledgerService = app(PartyLedgerService::class);

    $invoice = app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Service', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(10000), 'tax_rate' => 0],
        ],
    ), $user);

    expect($ledgerService->partyBalance($business, $customer, receivable: true))
        ->toBe(Money::rupeesToPaise(10000));

    app(PaymentService::class)->createAndConfirm(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'payment_type' => 'payment_in',
            'payment_date' => now()->toDateString(),
            'party_id' => $customer->id,
            'payment_mode' => 'cash',
            'account_id' => $cashAccount->id,
            'amount' => Money::rupeesToPaise(3000),
            'allocations' => [
                [
                    'allocatable_type' => 'sales_invoice',
                    'allocatable_id' => $invoice->id,
                    'allocated_amount' => Money::rupeesToPaise(3000),
                ],
            ],
        ],
    );

    expect($ledgerService->partyBalance($business, $customer, receivable: true))
        ->toBe(Money::rupeesToPaise(7000));
});

test('receivable ageing buckets outstanding by document age', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create([
        'opening_balance' => 0,
    ]);

    app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->subDays(45)->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Old invoice', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(5000), 'tax_rate' => 0],
        ],
    ), $user);

    app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->subDays(10)->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Recent invoice', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(2000), 'tax_rate' => 0],
        ],
    ), $user);

    $report = app(PartyLedgerService::class)->ageingReport(
        $business,
        receivable: true,
        asOf: Carbon::parse(now()->toDateString()),
    );

    expect($report['rows'])->toHaveCount(1);
    expect($report['rows'][0]['days_31_60'])->toBe(Money::rupeesToPaise(5000));
    expect($report['rows'][0]['current'])->toBe(Money::rupeesToPaise(2000));
    expect($report['totals']['total_outstanding'])->toBe(Money::rupeesToPaise(7000));
});

test('party statement shows invoices and payments with running balance', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $cashAccount = $this->accountByCode($business, '1100');

    $invoice = app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->subDays(5)->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Consulting', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(8000), 'tax_rate' => 0],
        ],
    ), $user);

    app(PaymentService::class)->createAndConfirm(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'payment_type' => 'payment_in',
            'payment_date' => now()->subDays(2)->toDateString(),
            'party_id' => $customer->id,
            'payment_mode' => 'cash',
            'account_id' => $cashAccount->id,
            'amount' => Money::rupeesToPaise(3000),
            'allocations' => [
                [
                    'allocatable_type' => 'sales_invoice',
                    'allocatable_id' => $invoice->id,
                    'allocated_amount' => Money::rupeesToPaise(3000),
                ],
            ],
        ],
    );

    $statement = app(PartyLedgerService::class)->statement(
        business: $business,
        party: $customer,
        from: Carbon::parse(now()->subDays(10)->toDateString()),
        to: Carbon::parse(now()->toDateString()),
        receivable: true,
    );

    expect($statement['closing_balance'])->toBe(Money::rupeesToPaise(5000));
    expect($statement['lines']->pluck('type')->all())->toContain('invoice', 'payment');
});

test('payable ageing includes supplier outstanding bills', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    app(PurchaseBillService::class)->confirm(app(PurchaseBillService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $supplier->id,
            'bill_date' => now()->subDays(20)->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Supplies', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(4500), 'tax_rate' => 0],
        ],
    ), $user);

    $report = app(PartyLedgerService::class)->ageingReport(
        $business,
        receivable: false,
        asOf: Carbon::parse(now()->toDateString()),
    );

    expect($report['rows'])->toHaveCount(1);
    expect($report['rows'][0]['party_name'])->toBe($supplier->name);
    expect($report['totals']['total_outstanding'])->toBe(Money::rupeesToPaise(4500));
});

test('report pages require appropriate permissions', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('reports.receivable-ageing'))
        ->assertOk();

    $this->actingAs($owner)
        ->get(route('reports.party-statement'))
        ->assertOk();

    $inventoryManager = User::factory()->create(['email' => 'inventory@test.com']);
    $this->attachUserToBusiness($inventoryManager, $business, SystemRoles::INVENTORY_MANAGER);

    $this->actingAs($inventoryManager)
        ->get(route('reports.receivable-ageing'))
        ->assertForbidden();
});
