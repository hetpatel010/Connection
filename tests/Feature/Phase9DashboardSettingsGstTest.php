<?php

use App\Application\Services\BusinessService;
use App\Application\Services\DashboardService;
use App\Application\Services\Gstr1ExportService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Carbon;

test('dashboard metrics reflect sales receivables and cash balances', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $cashAccount = $this->accountByCode($business, '1100');

    app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Cash,
            'payment_account_id' => $cashAccount->id,
        ],
        lines: [
            ['description' => 'Today sale', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(5000), 'tax_rate' => 0],
        ],
    ), $user);

    app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Credit sale', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(3000), 'tax_rate' => 0],
        ],
    ), $user);

    $metrics = app(DashboardService::class)->metrics($business, $financialYear);

    expect($metrics['todays_sales'])->toBe(Money::rupeesToPaise(8000));
    expect($metrics['receivables'])->toBe(Money::rupeesToPaise(3000));
    expect($metrics['cash_and_bank'])->toBeGreaterThanOrEqual(Money::rupeesToPaise(5000));
    expect($metrics['recent_invoices'])->toHaveCount(2);
});

test('business profile can be updated', function () {
    ['business' => $business] = $this->createUserWithBusiness();

    $updated = app(BusinessService::class)->updateProfile($business, [
        'name' => 'Acme Traders',
        'legal_name' => 'Acme Traders Pvt Ltd',
        'gstin' => '22AAAAA0000A1Z5',
        'state' => 'Maharashtra',
        'city' => 'Mumbai',
    ]);

    expect($updated->name)->toBe('Acme Traders');
    expect($updated->gstin)->toBe('22AAAAA0000A1Z5');
    expect($updated->state)->toBe('Maharashtra');
});

test('gstr1 export separates b2b and b2c invoices', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

    $b2bCustomer = Party::factory()->customer()->forBusiness($business)->create([
        'gstin' => '27AABCU9603R1ZM',
    ]);
    $b2cCustomer = Party::factory()->customer()->forBusiness($business)->create([
        'gstin' => null,
    ]);

    $service = app(SalesInvoiceService::class);

    $service->confirm($service->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $b2bCustomer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'B2B item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
        ],
    ), $user);

    $service->confirm($service->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $b2cCustomer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'B2C item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 5],
        ],
    ), $user);

    $export = app(Gstr1ExportService::class)->export(
        $business,
        Carbon::parse($financialYear->starts_on),
        now(),
    );

    expect($export['summary']['invoice_count'])->toBe(2);
    expect($export['b2b'])->toHaveCount(1);
    expect($export['b2c'])->toHaveCount(1);
    expect($export['b2b'][0]['customer_gstin'])->toBe('27AABCU9603R1ZM');
});

test('gstr1 export download requires manage_gst permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('accounting.gst-summary.gstr1-export'))
        ->assertOk()
        ->assertHeader('content-disposition');

    $salesperson = User::factory()->create(['email' => 'sales@test.com']);
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('accounting.gst-summary.gstr1-export'))
        ->assertForbidden();
});

test('business settings page requires manage_settings permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('settings.business'))
        ->assertOk();

    $cashier = User::factory()->create(['email' => 'cashier@test.com']);
    $this->attachUserToBusiness($cashier, $business, SystemRoles::CASHIER);

    $this->actingAs($cashier)
        ->get(route('settings.business'))
        ->assertForbidden();
});
