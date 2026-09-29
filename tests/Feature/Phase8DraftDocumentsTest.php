<?php

use App\Application\Services\PurchaseBillService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Shared\Exceptions\InvalidDocumentStateException;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Support\Money;

test('sales invoice can be saved as draft without posting a journal', function () {
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
            ['description' => 'Draft item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(2500), 'tax_rate' => 0],
        ],
    );

    expect($invoice->status)->toBe(DocumentStatus::Draft);
    expect($invoice->journal_id)->toBeNull();
    expect(Journal::query()->count())->toBe(0);
});

test('draft sales invoice can be updated and confirmed later', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $service = app(SalesInvoiceService::class);

    $invoice = $service->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Initial', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    );

    $invoice = $service->updateDraft(
        invoice: $invoice,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Updated item', 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    );

    expect($invoice->total_amount)->toBe(Money::rupeesToPaise(2000));
    expect($invoice->lines)->toHaveCount(1);
    expect($invoice->lines->first()->description)->toBe('Updated item');

    $invoice = $service->confirm($invoice, $user);

    expect($invoice->status)->toBe(DocumentStatus::Confirmed);
    expect($invoice->journal)->not->toBeNull();
    expect($invoice->journal->isBalanced())->toBeTrue();
});

test('draft sales invoice can be deleted', function () {
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
            ['description' => 'Temp', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 0],
        ],
    );

    app(SalesInvoiceService::class)->deleteDraft($invoice, $user);

    expect(SalesInvoice::query()->count())->toBe(0);
});

test('confirmed sales invoice cannot be updated or deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $service = app(SalesInvoiceService::class);

    $invoice = $service->confirm($service->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Confirmed', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    ), $user);

    expect(fn () => $service->updateDraft(
        invoice: $invoice,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Changed', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(2000), 'tax_rate' => 0],
        ],
    ))->toThrow(InvalidDocumentStateException::class);

    expect(fn () => $service->deleteDraft($invoice, $user))
        ->toThrow(InvalidDocumentStateException::class);
});

test('draft purchase bill workflow mirrors sales invoices', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();
    $service = app(PurchaseBillService::class);

    $bill = $service->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $supplier->id,
            'bill_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Draft supplies', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(3200), 'tax_rate' => 0],
        ],
    );

    expect($bill->status)->toBe(DocumentStatus::Draft);

    $bill = $service->confirm($bill, $user);

    expect($bill->status)->toBe(DocumentStatus::Confirmed);
    expect($bill->journal)->not->toBeNull();
});

test('draft edit routes require edit permissions', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $owner,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Draft', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    );

    $this->actingAs($owner)
        ->get(route('sales.invoices.edit', $invoice))
        ->assertOk();

    $accountant = User::factory()->create(['email' => 'accountant@test.com']);
    $this->attachUserToBusiness($accountant, $business, SystemRoles::ACCOUNTANT);

    $this->actingAs($accountant)
        ->get(route('sales.invoices.edit', $invoice))
        ->assertForbidden();
});

test('confirmed invoice edit route is forbidden', function () {
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
        ],
        lines: [
            ['description' => 'Done', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    ), $user);

    $this->actingAs($user)
        ->get(route('sales.invoices.edit', $invoice))
        ->assertForbidden();
});
