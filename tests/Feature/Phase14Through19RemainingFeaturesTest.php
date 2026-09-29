<?php

use App\Application\Services\BankReconciliationService;
use App\Application\Services\CreditNoteService;
use App\Application\Services\DebitNoteService;
use App\Application\Services\FinancialYearService;
use App\Application\Services\PaymentService;
use App\Application\Services\PurchaseBillService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\Party;
use App\Support\Money;
use Laravel\Sanctum\Sanctum;

test('confirmed purchase bill can be printed and downloaded', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

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

    $this->actingAs($user)
        ->get(route('purchases.bills.print', $bill))
        ->assertOk()
        ->assertSee($bill->bill_number);

    $this->actingAs($user)
        ->get(route('purchases.bills.download', $bill))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename="'.$bill->bill_number.'.html"');
});

test('confirmed sales invoice can be cancelled', function () {
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
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(800), 'tax_rate' => 0],
        ],
    ), $user);

    $originalJournalId = $invoice->journal_id;

    $invoice = app(SalesInvoiceService::class)->cancel($invoice, $user);

    expect($invoice->status)->toBe(DocumentStatus::Cancelled)
        ->and(Journal::query()->find($originalJournalId)->status->value)->toBe('reversed');
});

test('credit note can be issued from a confirmed sales invoice', function () {
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
            ['description' => 'Returned item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1200), 'tax_rate' => 18],
        ],
    ), $user);

    $creditNote = app(CreditNoteService::class)->createFromInvoice($invoice, $user, 'Customer return');

    expect($creditNote->status)->toBe(DocumentStatus::Confirmed)
        ->and($creditNote->source_sales_invoice_id)->toBe($invoice->id)
        ->and($creditNote->total_amount)->toBe($invoice->total_amount)
        ->and($creditNote->journal)->not->toBeNull()
        ->and(CreditNote::query()->count())->toBe(1);
});

test('debit note can be issued from a confirmed purchase bill', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

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
            ['description' => 'Defective stock', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(600), 'tax_rate' => 0],
        ],
    ), $user);

    $debitNote = app(DebitNoteService::class)->createFromBill($bill, $user, 'Return to supplier');

    expect($debitNote->status)->toBe(DocumentStatus::Confirmed)
        ->and($debitNote->source_purchase_bill_id)->toBe($bill->id)
        ->and($debitNote->journal)->not->toBeNull()
        ->and(DebitNote::query()->count())->toBe(1);
});

test('opening balances are carried forward when the next financial year opens', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

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
            ['description' => 'Service', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(5000), 'tax_rate' => 0],
        ],
    ), $user);

    app(FinancialYearService::class)->close($financialYear, $user);
    $nextYear = app(FinancialYearService::class)->openNext($business, $user);

    expect($nextYear->is_current)->toBeTrue()
        ->and(Journal::query()->withoutGlobalScopes()->where('financial_year_id', $nextYear->id)->where('description', 'Opening balances')->exists())->toBeTrue();
});

test('bank statement line can be matched to a confirmed bank payment', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $bankAccount = $this->accountByCode($business, '1200');
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $payment = app(PaymentService::class)->createAndConfirm(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'payment_type' => 'payment_in',
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'bank',
            'account_id' => $bankAccount->id,
            'party_id' => $customer->id,
            'amount' => Money::rupeesToPaise(1500),
        ],
    );

    $line = app(BankReconciliationService::class)->addStatementLine($business, $bankAccount, [
        'transaction_date' => now()->toDateString(),
        'description' => 'NEFT receipt',
        'reference' => 'UTR123',
        'amount' => Money::rupeesToPaise(1500),
    ]);

    $matched = app(BankReconciliationService::class)->matchToPayment($line, $payment);

    expect($matched->matched_payment_id)->toBe($payment->id)
        ->and($matched->matched_at)->not->toBeNull();
});

test('api v1 customers endpoint returns json envelope for authenticated user', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    Party::factory()->customer()->forBusiness($business)->create(['name' => 'API Customer']);

    Sanctum::actingAs($user);

    $this->withHeaders(['X-Business-Id' => $business->id])
        ->getJson('/api/v1/customers')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.name', 'API Customer');
});

test('api v1 requires business header', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/customers')
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

test('credit notes and bank reconciliation pages are accessible to authorized users', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('sales.credit-notes.index'))
        ->assertOk();

    $this->actingAs($owner)
        ->get(route('accounting.bank-reconciliation'))
        ->assertOk();
});
