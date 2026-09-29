<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\ExcelExportWriter;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\User;
use App\Support\Money;

test('list excel export downloads products for current business', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    Product::factory()->forBusiness($business)->create(['name' => 'Export Widget']);

    $response = $this->actingAs($user)
        ->get(route('exports.list-excel', ['module' => 'products']));

    $response->assertOk();
    $response->assertDownload();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
});

test('list excel export accepts search query parameter', function () {
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
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 18],
        ],
    );

    $this->actingAs($user)
        ->get(route('exports.list-excel', ['module' => 'sales-invoices', 'search' => 'NONEXISTENT']))
        ->assertOk()
        ->assertDownload();
});

test('list excel export rejects unknown module', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    $this->actingAs($user)
        ->get(route('exports.list-excel', ['module' => 'unknown-module']))
        ->assertNotFound();
});

test('list excel export requires module permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness();

    $cashier = User::factory()->create();
    $this->attachUserToBusiness($cashier, $business, SystemRoles::CASHIER);
    app(BusinessContext::class)->setBusiness($business, $cashier);

    $this->actingAs($cashier)
        ->get(route('exports.list-excel', ['module' => 'products']))
        ->assertForbidden();
});

test('list excel export returns a formatted customers workbook with unchanged data', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    Party::factory()->customer()->forBusiness($business)->create([
        'name' => 'Sharma Traders',
        'phone' => '09876543210',
        'gstin' => '27AAECS1234F1Z5',
        'billing_city' => 'Ahmedabad',
        'billing_state' => 'Gujarat',
        'credit_limit' => 25000000,
    ]);

    $response = $this->actingAs($user)
        ->get(route('exports.list-excel', ['module' => 'customers']));

    $response->assertOk();

    expect($response->headers->get('content-type'))
        ->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->and($response->headers->get('content-disposition'))
        ->toContain('attachment;')
        ->toContain($business->slug.'-customers-'.now()->format('Y-m-d').'.xlsx');

    $path = $response->baseResponse->getFile()->getPathname();

    $sheet = xlsxSheet($path);
    $styles = xlsxEntry($path, 'xl/styles.xml');

    // The eight documented columns, in order, in the header row.
    expect($sheet)
        ->toContain('Name', 'Phone', 'Email', 'GSTIN', 'City', 'State', 'Credit Limit (₹)', 'Status')
        ->toContain('state="frozen"')
        ->toContain('<autoFilter ref="A1:H2"/>')
        ->toContain('<cols>');

    expect($styles)->toContain('fgColor rgb="'.ExcelExportWriter::HEADER_BACKGROUND_COLOR.'"');

    expect(xlsxNumberFormats($path))->toContain('@', '#,##0.00');

    // Data is intact: the phone and GSTIN are text, the credit limit stays numeric.
    expect($sheet)
        ->toContain('Sharma Traders')
        ->toContain('09876543210')
        ->toContain('27AAECS1234F1Z5')
        ->toContain('Ahmedabad')
        ->toContain('250000');

    expect(xlsxCell($sheet, 'B2'))->toContain('t="inlineStr"')
        ->and(xlsxCell($sheet, 'D2'))->toContain('t="inlineStr"');
});
