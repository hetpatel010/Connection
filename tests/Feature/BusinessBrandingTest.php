<?php

use App\Application\Services\BusinessBrandingService;
use App\Application\Services\BusinessContext;
use App\Application\Services\InvoiceDocumentService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('business branding settings persist terms and bank details', function () {
    ['business' => $business] = $this->createUserWithBusiness();

    $updated = app(BusinessBrandingService::class)->update($business, [
        'terms_and_conditions' => 'Goods sold are not returnable.',
        'invoice_footer' => 'Thank you!',
        'bank_name' => 'HDFC Bank',
        'bank_account' => '1234567890',
        'bank_ifsc' => 'HDFC0001234',
        'upi_id' => 'shop@upi',
        'website' => 'https://example.com',
    ]);

    $branding = app(BusinessBrandingService::class)->forBusiness($updated);

    expect($branding['terms_and_conditions'])->toBe('Goods sold are not returnable.');
    expect($branding['bank_name'])->toBe('HDFC Bank');
    expect($branding['bank_details'])->toContain('HDFC0001234');
    expect($branding['website'])->toBe('https://example.com');
});

test('business logo appears in app sidebar brand', function () {
    Storage::fake('public');

    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness([
        'name' => 'Demo Traders Pvt Ltd',
    ]);

    $logo = UploadedFile::fake()->image('logo.png', 300, 100);
    app(BusinessBrandingService::class)->update($business, [], $logo);

    $logoUrl = app(BusinessBrandingService::class)->forBusiness($business->fresh())['logo_url'];

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Demo Traders Pvt Ltd', false)
        ->assertSee($logoUrl, false)
        ->assertSee('class="h-10 w-auto max-w-full object-contain object-center"', false)
        ->assertSee('app-sidebar', false);
});

test('business logo can be stored and removed', function () {
    Storage::fake('public');

    ['business' => $business] = $this->createUserWithBusiness();
    $service = app(BusinessBrandingService::class);

    $logo = UploadedFile::fake()->image('logo.png', 300, 100);

    $updated = $service->update($business, [], $logo);

    $branding = $service->forBusiness($updated);
    expect($branding['logo_path'])->not->toBeNull();
    Storage::disk('public')->assertExists($branding['logo_path']);

    $service->removeLogo($updated->fresh());

    expect($service->forBusiness($updated->fresh())['logo_path'])->toBeNull();
});

test('confirmed invoice document includes terms and conditions', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    app(BusinessBrandingService::class)->update($business, [
        'terms_and_conditions' => 'All disputes subject to Ahmedabad jurisdiction.',
        'bank_name' => 'SBI',
        'bank_account' => '9999888877',
    ]);

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $invoice = app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
        business: $business->fresh(),
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Branded item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 0],
        ],
    ), $user);

    $html = app(InvoiceDocumentService::class)->render($invoice, $business->fresh());

    expect($html)->toContain('All disputes subject to Ahmedabad jurisdiction.');
    expect($html)->toContain('Bank Details');
    expect($html)->toContain('Authorised Signatory');
});
