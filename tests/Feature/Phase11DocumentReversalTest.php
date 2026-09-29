<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\ExpenseService;
use App\Application\Services\InvoiceDocumentService;
use App\Application\Services\PaymentService;
use App\Application\Services\PurchaseBillService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Livewire\Sales\InvoicesIndex;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\User;
use App\Support\Money;
use Livewire\Livewire;

test('confirmed invoice can be downloaded as html attachment', function () {
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
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    ), $user);

    $this->actingAs($user)
        ->get(route('sales.invoices.download', $invoice))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="'.$invoice->invoice_number.'.html"')
        ->assertSee($invoice->invoice_number, false)
        ->assertSee($customer->name, false);
});

test('draft invoice download route returns not found', function () {
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

    $this->actingAs($user)
        ->get(route('sales.invoices.download', $invoice))
        ->assertNotFound();
});

test('invoice document service renders standalone html', function () {
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
            ['description' => 'Service', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 0],
        ],
    ), $user);

    $html = app(InvoiceDocumentService::class)->render($invoice, $business);

    expect($html)->toContain('<!DOCTYPE html>')
        ->and($html)->toContain($invoice->invoice_number)
        ->and($html)->toContain(e($customer->name));
});

test('confirmed invoice print view includes download toolbar', function () {
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
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    ), $user);

    $this->actingAs($user)
        ->get(route('sales.invoices.print', $invoice))
        ->assertOk()
        ->assertSee('Download')
        ->assertSee(route('sales.invoices.download', $invoice), false);
});

test('owner can reverse confirmed invoice from invoices index', function () {
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
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    ), $user);

    Livewire::actingAs($user)
        ->test(InvoicesIndex::class)
        ->call('reverseInvoice', $invoice->id)
        ->assertHasNoErrors();

    expect($invoice->fresh()->status)->toBe(DocumentStatus::Reversed);
});

test('salesperson cannot reverse invoice', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $invoice = app(SalesInvoiceService::class)->confirm(app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $owner,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    ), $owner);

    $salesperson = User::factory()->create(['email' => 'sales@test.com']);
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);
    app(BusinessContext::class)->setBusiness($business, $salesperson);

    Livewire::actingAs($salesperson)
        ->test(InvoicesIndex::class)
        ->call('reverseInvoice', $invoice->id)
        ->assertForbidden();

    expect($invoice->fresh()->status)->toBe(DocumentStatus::Confirmed);
});

test('owner can reverse confirmed purchase bill payment and expense', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();
    $cashAccount = $this->accountByCode($business, '1100');
    $rentAccount = $this->accountByCode($business, '5300');

    $bill = app(PurchaseBillService::class)->confirm(app(PurchaseBillService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $supplier->id,
            'bill_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Supplies', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 0],
        ],
    ), $user);

    $payment = app(PaymentService::class)->createAndConfirm(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'payment_type' => 'payment_out',
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'cash',
            'account_id' => $cashAccount->id,
            'amount' => Money::rupeesToPaise(200),
        ],
    );

    $expense = app(ExpenseService::class)->createAndConfirm(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'expense_date' => now()->toDateString(),
            'expense_account_id' => $rentAccount->id,
            'payment_account_id' => $cashAccount->id,
            'amount' => Money::rupeesToPaise(300),
            'tax_amount' => 0,
            'description' => 'Rent',
        ],
    );

    app(PurchaseBillService::class)->reverse($bill, $user);
    app(PaymentService::class)->reverse($payment, $user);
    app(ExpenseService::class)->reverse($expense, $user);

    expect($bill->fresh()->status)->toBe(DocumentStatus::Reversed)
        ->and($payment->fresh()->status)->toBe(DocumentStatus::Reversed)
        ->and($expense->fresh()->status)->toBe(DocumentStatus::Reversed);
});
