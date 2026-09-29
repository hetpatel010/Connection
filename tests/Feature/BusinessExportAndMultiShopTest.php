<?php

use App\Application\Services\BusinessExcelExportService;
use App\Application\Services\BusinessService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Livewire\Business\CreateBusiness;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Support\Money;
use Livewire\Livewire;

test('excel export downloads xlsx workbook for active financial year', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Export test item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
        ],
    );

    $path = app(BusinessExcelExportService::class)->buildWorkbook($business, $financialYear);

    expect(is_file($path))->toBeTrue();
    expect(filesize($path))->toBeGreaterThan(1000);

    app(BusinessExcelExportService::class)->cleanup($path);
});

test('authenticated user can download excel export via route', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    $response = $this->actingAs($user)
        ->get(route('settings.export.excel'));

    $response->assertOk();
    $response->assertDownload();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
});

test('user can create a second shop and switch between businesses', function () {
    ['user' => $user, 'business' => $firstBusiness] = $this->createUserWithBusiness();

    expect($user->businesses()->count())->toBe(1);

    $secondBusiness = app(BusinessService::class)->createForOwner($user, [
        'name' => 'Second Shop',
        'state' => 'Maharashtra',
    ]);

    expect($user->fresh()->businesses()->count())->toBe(2);
    expect($secondBusiness->name)->toBe('Second Shop');
    expect($user->belongsToBusiness($secondBusiness))->toBeTrue();
});

test('create shop page saves new business and redirects to dashboard', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    Livewire::actingAs($user)
        ->test(CreateBusiness::class)
        ->set('name', 'Brand Outlet')
        ->set('state', 'Gujarat')
        ->set('gstin', '24AABCU9603R1ZM')
        ->call('save')
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->businesses()->where('name', 'Brand Outlet')->exists())->toBeTrue();
});
