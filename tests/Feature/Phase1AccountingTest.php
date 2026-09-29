<?php

use App\Application\Services\AccountingService;
use App\Application\Services\FinancialYearService;
use App\Application\Services\LedgerService;
use App\Application\Services\TrialBalanceService;
use App\Domain\Accounting\DTOs\JournalLineData;
use App\Domain\Accounting\Enums\JournalStatus;
use App\Domain\Accounting\Exceptions\InvalidJournalLineException;
use App\Domain\Accounting\Exceptions\UnbalancedJournalException;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Shared\Exceptions\FinancialYearClosedException;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\User;
use App\Support\Money;

test('default chart of accounts is seeded when a business is created', function () {
    ['business' => $business] = $this->createUserWithBusiness();

    expect(Account::query()->withoutGlobalScopes()->where('business_id', $business->id)->count())->toBe(20);
    expect($this->accountByCode($business, '1100')->name)->toBe('Cash');
    expect($this->accountByCode($business, '4100')->name)->toBe('Sales');
});

test('posted journal satisfies debit equals credit invariant', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->firstOrFail();

    $cash = $this->accountByCode($business, '1100');
    $sales = $this->accountByCode($business, '4100');

    $journal = app(AccountingService::class)->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: now()->toDateString(),
        description: 'Test sale',
        lines: [
            new JournalLineData(accountId: $cash->id, debitAmount: Money::rupeesToPaise(1000)),
            new JournalLineData(accountId: $sales->id, creditAmount: Money::rupeesToPaise(1000)),
        ],
    );

    expect($journal->isBalanced())->toBeTrue();
    expect($journal->totalDebits())->toBe($journal->totalCredits());
    expect($journal->status)->toBe(JournalStatus::Posted);
});

test('unbalanced journal is rejected before persistence', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->firstOrFail();

    $cash = $this->accountByCode($business, '1100');
    $sales = $this->accountByCode($business, '4100');

    expect(fn () => app(AccountingService::class)->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: now()->toDateString(),
        description: 'Invalid entry',
        lines: [
            new JournalLineData(accountId: $cash->id, debitAmount: Money::rupeesToPaise(1000)),
            new JournalLineData(accountId: $sales->id, creditAmount: Money::rupeesToPaise(900)),
        ],
    ))->toThrow(UnbalancedJournalException::class);

    expect(Journal::query()->withoutGlobalScopes()->count())->toBe(0);
});

test('journal cannot be posted into a closed financial year', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->firstOrFail();

    app(FinancialYearService::class)->close($financialYear, $user);

    $cash = $this->accountByCode($business, '1100');
    $sales = $this->accountByCode($business, '4100');

    expect(fn () => app(AccountingService::class)->postJournal(
        business: $business,
        financialYear: $financialYear->fresh(),
        user: $user,
        transactionDate: now()->toDateString(),
        description: 'Should fail',
        lines: [
            new JournalLineData(accountId: $cash->id, debitAmount: Money::rupeesToPaise(500)),
            new JournalLineData(accountId: $sales->id, creditAmount: Money::rupeesToPaise(500)),
        ],
    ))->toThrow(FinancialYearClosedException::class);
});

test('journal reversal creates offsetting entries and marks original as reversed', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->firstOrFail();

    $cash = $this->accountByCode($business, '1100');
    $sales = $this->accountByCode($business, '4100');

    $original = app(AccountingService::class)->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: now()->toDateString(),
        description: 'Sale to reverse',
        lines: [
            new JournalLineData(accountId: $cash->id, debitAmount: Money::rupeesToPaise(2500)),
            new JournalLineData(accountId: $sales->id, creditAmount: Money::rupeesToPaise(2500)),
        ],
    );

    $reversal = app(AccountingService::class)->reverseJournal($original, $user);

    expect($reversal->isBalanced())->toBeTrue();
    expect($original->fresh()->status)->toBe(JournalStatus::Reversed);
    expect($original->fresh()->reversed_by_id)->toBe($reversal->id);
});

test('ledger service calculates running balance for an account', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->firstOrFail();

    $cash = $this->accountByCode($business, '1100');
    $sales = $this->accountByCode($business, '4100');
    $bank = $this->accountByCode($business, '1200');

    $accounting = app(AccountingService::class);

    $accounting->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: '2025-04-05',
        description: 'Cash sale',
        lines: [
            new JournalLineData(accountId: $cash->id, debitAmount: Money::rupeesToPaise(1000)),
            new JournalLineData(accountId: $sales->id, creditAmount: Money::rupeesToPaise(1000)),
        ],
    );

    $accounting->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: '2025-04-10',
        description: 'Deposit to bank',
        lines: [
            new JournalLineData(accountId: $bank->id, debitAmount: Money::rupeesToPaise(400)),
            new JournalLineData(accountId: $cash->id, creditAmount: Money::rupeesToPaise(400)),
        ],
    );

    $ledger = app(LedgerService::class)->accountLedger($cash, $financialYear);

    expect($ledger)->toHaveCount(2);
    expect($ledger->last()['running_balance'])->toBe(Money::rupeesToPaise(600));
});

test('trial balance totals remain balanced after postings', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->firstOrFail();

    $cash = $this->accountByCode($business, '1100');
    $sales = $this->accountByCode($business, '4100');
    $rent = $this->accountByCode($business, '5300');

    app(AccountingService::class)->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: now()->toDateString(),
        description: 'Cash sale',
        lines: [
            new JournalLineData(accountId: $cash->id, debitAmount: Money::rupeesToPaise(5000)),
            new JournalLineData(accountId: $sales->id, creditAmount: Money::rupeesToPaise(5000)),
        ],
    );

    app(AccountingService::class)->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: now()->toDateString(),
        description: 'Rent paid',
        lines: [
            new JournalLineData(accountId: $rent->id, debitAmount: Money::rupeesToPaise(2000)),
            new JournalLineData(accountId: $cash->id, creditAmount: Money::rupeesToPaise(2000)),
        ],
    );

    $report = app(TrialBalanceService::class)->generate($business, $financialYear);

    expect($report['total_debits'])->toBe($report['total_credits']);
    expect($report['total_debits'])->toBeGreaterThan(0);
});

test('group header accounts cannot be posted to', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->firstOrFail();

    $assets = $this->accountByCode($business, '1000');
    $sales = $this->accountByCode($business, '4100');

    expect(fn () => app(AccountingService::class)->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: now()->toDateString(),
        description: 'Invalid header posting',
        lines: [
            new JournalLineData(accountId: $assets->id, debitAmount: Money::rupeesToPaise(100)),
            new JournalLineData(accountId: $sales->id, creditAmount: Money::rupeesToPaise(100)),
        ],
    ))->toThrow(InvalidJournalLineException::class);
});

test('accounting pages require appropriate permissions', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('accounting.ledger'))
        ->assertOk();

    $outsider = User::factory()->create(['email' => 'outsider@test.com']);
    $this->attachUserToBusiness($outsider, $business, SystemRoles::SALESPERSON);

    $this->actingAs($outsider)
        ->get(route('accounting.ledger'))
        ->assertForbidden();
});
