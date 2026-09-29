<?php

use App\Application\Services\PaymentAllocationService;
use App\Application\Services\PaymentService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Shared\Exceptions\InvalidPaymentAllocationException;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\PaymentAllocation;
use App\Support\Money;

test('payment allocation reduces invoice outstanding balance', function () {
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
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Consulting', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(10000), 'tax_rate' => 0],
        ],
    );
    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);

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
            'amount' => Money::rupeesToPaise(4000),
            'allocations' => [
                [
                    'allocatable_type' => 'sales_invoice',
                    'allocatable_id' => $invoice->id,
                    'allocated_amount' => Money::rupeesToPaise(4000),
                ],
            ],
        ],
    );

    $allocationService = app(PaymentAllocationService::class);

    expect($allocationService->allocatedAmount($invoice))->toBe(Money::rupeesToPaise(4000));
    expect($allocationService->outstandingBalance($invoice))->toBe(Money::rupeesToPaise(6000));
    expect(PaymentAllocation::query()->count())->toBe(1);
});

test('payment can be split across multiple invoices', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $cashAccount = $this->accountByCode($business, '1100');
    $salesService = app(SalesInvoiceService::class);

    $firstInvoice = $salesService->confirm($salesService->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Item A', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(3000), 'tax_rate' => 0],
        ],
    ), $user);

    $secondInvoice = $salesService->confirm($salesService->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Item B', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(2000), 'tax_rate' => 0],
        ],
    ), $user);

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
            'amount' => Money::rupeesToPaise(4500),
            'allocations' => [
                [
                    'allocatable_type' => 'sales_invoice',
                    'allocatable_id' => $firstInvoice->id,
                    'allocated_amount' => Money::rupeesToPaise(3000),
                ],
                [
                    'allocatable_type' => 'sales_invoice',
                    'allocatable_id' => $secondInvoice->id,
                    'allocated_amount' => Money::rupeesToPaise(1500),
                ],
            ],
        ],
    );

    $allocationService = app(PaymentAllocationService::class);

    expect($allocationService->outstandingBalance($firstInvoice))->toBe(0);
    expect($allocationService->outstandingBalance($secondInvoice))->toBe(Money::rupeesToPaise(500));
    expect(PaymentAllocation::query()->count())->toBe(2);
});

test('allocation cannot exceed invoice outstanding balance', function () {
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
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Service', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    ), $user);

    expect(fn () => app(PaymentService::class)->createAndConfirm(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'payment_type' => 'payment_in',
            'payment_date' => now()->toDateString(),
            'party_id' => $customer->id,
            'payment_mode' => 'cash',
            'account_id' => $cashAccount->id,
            'amount' => Money::rupeesToPaise(1500),
            'allocations' => [
                [
                    'allocatable_type' => 'sales_invoice',
                    'allocatable_id' => $invoice->id,
                    'allocated_amount' => Money::rupeesToPaise(1500),
                ],
            ],
        ],
    ))->toThrow(InvalidPaymentAllocationException::class);

    expect(PaymentAllocation::query()->count())->toBe(0);
});

test('reversed payment allocations no longer count toward outstanding', function () {
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
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Service', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(5000), 'tax_rate' => 0],
        ],
    ), $user);

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
            'amount' => Money::rupeesToPaise(2000),
            'allocations' => [
                [
                    'allocatable_type' => 'sales_invoice',
                    'allocatable_id' => $invoice->id,
                    'allocated_amount' => Money::rupeesToPaise(2000),
                ],
            ],
        ],
    );

    app(PaymentService::class)->reverse($payment, $user);

    $allocationService = app(PaymentAllocationService::class);

    expect($payment->fresh()->status)->toBe(DocumentStatus::Reversed);
    expect($allocationService->allocatedAmount($invoice))->toBe(0);
    expect($allocationService->outstandingBalance($invoice))->toBe(Money::rupeesToPaise(5000));
});

test('cash invoices have zero outstanding balance', function () {
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
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Cash,
            'payment_account_id' => $cashAccount->id,
        ],
        lines: [
            ['description' => 'Cash sale', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 0],
        ],
    ), $user);

    expect(app(PaymentAllocationService::class)->outstandingBalance($invoice))->toBe(0);
    expect(app(PaymentAllocationService::class)->openSalesInvoices($business, $customer->id))->toBeEmpty();
});

test('open sales invoices only includes credit documents with balance due', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $salesService = app(SalesInvoiceService::class);

    $openInvoice = $salesService->confirm($salesService->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Open', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    ), $user);

    $salesService->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Draft', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 0],
        ],
    );

    $openDocuments = app(PaymentAllocationService::class)->openSalesInvoices($business, $customer->id);

    expect($openDocuments)->toHaveCount(1);
    expect($openDocuments->first()->id)->toBe($openInvoice->id);
});
