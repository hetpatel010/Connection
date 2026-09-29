<?php

use App\Application\Services\ExpenseService;
use App\Application\Services\PaymentService;
use App\Application\Services\PurchaseBillService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\Party;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Unit;
use App\Models\User;
use App\Support\Money;

test('credit sales invoice posts balanced journal to accounts receivable and sales', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'opening_stock' => 10,
        'selling_price' => Money::rupeesToPaise(1000),
        'tax_rate' => 18,
    ]);

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
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);

    expect($invoice->status)->toBe(DocumentStatus::Confirmed);
    expect($invoice->total_amount)->toBe(Money::rupeesToPaise(1180));
    expect($invoice->journal)->not->toBeNull();
    expect($invoice->journal->isBalanced())->toBeTrue();

    $arAccount = $this->accountByCode($business, '1300');
    $salesAccount = $this->accountByCode($business, '4100');
    $gstAccount = $this->accountByCode($business, '2200');

    $lines = $invoice->journal->lines->keyBy('account_id');
    expect($lines[$arAccount->id]->debit_amount)->toBe(Money::rupeesToPaise(1180));
    expect($lines[$salesAccount->id]->credit_amount)->toBe(Money::rupeesToPaise(1000));
    expect($lines[$gstAccount->id]->credit_amount)->toBe(Money::rupeesToPaise(180));
});

test('cash sales invoice debits cash instead of accounts receivable', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $cashAccount = $this->accountByCode($business, '1100');

    $invoice = app(SalesInvoiceService::class)->createDraft(
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
            ['description' => 'Service fee', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 0],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);

    $lines = $invoice->journal->lines->keyBy('account_id');
    expect($lines[$cashAccount->id]->debit_amount)->toBe(Money::rupeesToPaise(500));
});

test('purchase bill posts balanced journal to accounts payable and purchase', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    $bill = app(PurchaseBillService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $supplier->id,
            'bill_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Raw materials', 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(200), 'tax_rate' => 5],
        ],
    );

    $bill = app(PurchaseBillService::class)->confirm($bill, $user);

    expect($bill->status)->toBe(DocumentStatus::Confirmed);
    expect($bill->journal->isBalanced())->toBeTrue();
    expect($bill->total_amount)->toBe(Money::rupeesToPaise(420));
});

test('payment in posts debit cash and credit accounts receivable', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $cashAccount = $this->accountByCode($business, '1100');

    $payment = app(PaymentService::class)->createAndConfirm(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'payment_type' => 'payment_in',
            'payment_date' => now()->toDateString(),
            'party_id' => $customer->id,
            'payment_mode' => 'cash',
            'account_id' => $cashAccount->id,
            'amount' => Money::rupeesToPaise(1000),
        ],
    );

    expect($payment->status)->toBe(DocumentStatus::Confirmed);
    expect($payment->journal->isBalanced())->toBeTrue();

    $arAccount = $this->accountByCode($business, '1300');
    $lines = $payment->journal->lines->keyBy('account_id');
    expect($lines[$cashAccount->id]->debit_amount)->toBe(Money::rupeesToPaise(1000));
    expect($lines[$arAccount->id]->credit_amount)->toBe(Money::rupeesToPaise(1000));
});

test('payment out posts debit accounts payable and credit cash', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $cashAccount = $this->accountByCode($business, '1100');

    $payment = app(PaymentService::class)->createAndConfirm(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'payment_type' => 'payment_out',
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'cash',
            'account_id' => $cashAccount->id,
            'amount' => Money::rupeesToPaise(750),
        ],
    );

    $apAccount = $this->accountByCode($business, '2100');
    $lines = $payment->journal->lines->keyBy('account_id');
    expect($lines[$apAccount->id]->debit_amount)->toBe(Money::rupeesToPaise(750));
    expect($lines[$cashAccount->id]->credit_amount)->toBe(Money::rupeesToPaise(750));
});

test('expense posts debit expense account and credit cash', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $rentAccount = $this->accountByCode($business, '5300');
    $cashAccount = $this->accountByCode($business, '1100');

    $expense = app(ExpenseService::class)->createAndConfirm(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'expense_date' => now()->toDateString(),
            'expense_account_id' => $rentAccount->id,
            'payment_account_id' => $cashAccount->id,
            'amount' => Money::rupeesToPaise(15000),
            'tax_amount' => 0,
            'description' => 'Office rent',
        ],
    );

    expect($expense->status)->toBe(DocumentStatus::Confirmed);
    $lines = $expense->journal->lines->keyBy('account_id');
    expect($lines[$rentAccount->id]->debit_amount)->toBe(Money::rupeesToPaise(15000));
    expect($lines[$cashAccount->id]->credit_amount)->toBe(Money::rupeesToPaise(15000));
});

test('confirmed sales invoice can be reversed', function () {
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
            ['description' => 'Consulting', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(2000), 'tax_rate' => 0],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);
    $originalJournalId = $invoice->journal_id;

    $invoice = app(SalesInvoiceService::class)->reverse($invoice, $user);

    expect($invoice->status)->toBe(DocumentStatus::Reversed);
    expect(Journal::query()->find($originalJournalId)->status->value)->toBe('reversed');
});

test('transaction pages require appropriate permissions', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('sales.invoices.index'))
        ->assertOk();

    $accountant = User::factory()->create(['email' => 'accountant@test.com']);
    $this->attachUserToBusiness($accountant, $business, SystemRoles::ACCOUNTANT);

    $this->actingAs($accountant)
        ->get(route('sales.invoices.index'))
        ->assertOk();

    $this->actingAs($accountant)
        ->get(route('sales.invoices.create'))
        ->assertForbidden();

    $inventoryManager = User::factory()->create(['email' => 'inventory@test.com']);
    $this->attachUserToBusiness($inventoryManager, $business, SystemRoles::INVENTORY_MANAGER);

    $this->actingAs($inventoryManager)
        ->get(route('sales.invoices.index'))
        ->assertForbidden();
});

test('sales invoices are scoped to current business', function () {
    ['user' => $user, 'business' => $firstBusiness] = $this->createUserWithBusiness(['email' => 'owner1@test.com']);

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $firstBusiness->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($firstBusiness)->create();

    app(SalesInvoiceService::class)->createDraft(
        business: $firstBusiness,
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

    expect(SalesInvoice::query()->count())->toBe(1);
});
