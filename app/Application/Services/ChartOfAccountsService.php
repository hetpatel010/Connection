<?php

namespace App\Application\Services;

use App\Domain\Accounting\DefaultChartOfAccounts;
use App\Domain\Accounting\DTOs\JournalLineData;
use App\Domain\Accounting\Enums\AccountStatus;
use App\Domain\Accounting\Enums\BalanceSide;
use App\Domain\Accounting\Exceptions\AccountHasTransactionsException;
use App\Domain\Shared\Exceptions\FinancialYearClosedException;
use App\Models\Account;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsService
{
    public function seedDefaultForBusiness(Business $business, FinancialYear $financialYear, User $user): Collection
    {
        if (Account::query()->where('business_id', $business->id)->exists()) {
            return Account::query()->where('business_id', $business->id)->get();
        }

        return DB::transaction(function () use ($business): Collection {
            $accountsByCode = [];

            foreach (DefaultChartOfAccounts::accounts() as $definition) {
                $account = Account::query()->create([
                    'business_id' => $business->id,
                    'parent_id' => null,
                    'name' => $definition['name'],
                    'code' => $definition['code'],
                    'type' => $definition['type'],
                    'group' => $definition['group'],
                    'status' => AccountStatus::Active,
                    'is_system' => $definition['is_system'],
                    'description' => $definition['description'],
                ]);

                $accountsByCode[$definition['code']] = $account;
            }

            foreach (DefaultChartOfAccounts::accounts() as $definition) {
                if ($definition['parent_code'] === null) {
                    continue;
                }

                $account = $accountsByCode[$definition['code']];
                $account->update([
                    'parent_id' => $accountsByCode[$definition['parent_code']]->id,
                ]);
            }

            return collect($accountsByCode)->values();
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createAccount(Business $business, array $attributes): Account
    {
        $parentId = $attributes['parent_id'] ?? null;

        if ($parentId !== null) {
            $this->assertParentBelongsToBusiness($business, $parentId);
        }

        return Account::query()->create([
            'business_id' => $business->id,
            'parent_id' => $parentId,
            'name' => $attributes['name'],
            'code' => $attributes['code'],
            'type' => $attributes['type'],
            'group' => $attributes['group'],
            'status' => $attributes['status'] ?? AccountStatus::Active,
            'is_system' => $attributes['is_system'] ?? false,
            'description' => $attributes['description'] ?? null,
            'opening_balance' => $attributes['opening_balance'] ?? 0,
            'opening_balance_side' => $attributes['opening_balance_side'] ?? null,
        ]);
    }

    /**
     * Updates an account while protecting historical accounting data.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws AccountHasTransactionsException
     */
    public function updateAccount(Account $account, array $attributes): Account
    {
        $locked = $this->lockedAttributes($account);

        if ($locked['code'] && array_key_exists('code', $attributes)) {
            throw AccountHasTransactionsException::forLockedAttribute($account->code, 'code');
        }

        if ($locked['type'] && array_key_exists('type', $attributes)) {
            throw AccountHasTransactionsException::forLockedAttribute($account->code, 'type');
        }

        if (array_key_exists('parent_id', $attributes)) {
            $this->assertParentAssignable($account, $attributes['parent_id']);
        }

        $updatable = [
            'name',
            'code',
            'type',
            'group',
            'status',
            'description',
            'parent_id',
            'opening_balance',
            'opening_balance_side',
        ];

        if ($locked['code']) {
            $updatable = array_values(array_diff($updatable, ['code']));
        }

        if ($locked['type']) {
            $updatable = array_values(array_diff($updatable, ['type']));
        }

        if ($locked['opening_balance']) {
            $updatable = array_values(array_diff($updatable, ['opening_balance', 'opening_balance_side']));
        }

        $changes = array_intersect_key($attributes, array_flip($updatable));

        if ($changes !== []) {
            $account->update($changes);
        }

        return $account->refresh();
    }

    public function setStatus(Account $account, AccountStatus $status): Account
    {
        $account->update(['status' => $status]);

        return $account->refresh();
    }

    /**
     * Soft deletes an account once it is proven free of dependent records.
     *
     * @throws AccountHasTransactionsException
     */
    public function deleteAccount(Account $account): void
    {
        $this->assertDeletable($account);

        $account->delete();
    }

    /**
     * @throws AccountHasTransactionsException
     */
    public function assertDeletable(Account $account): void
    {
        if ($account->children()->exists()) {
            throw AccountHasTransactionsException::forAccountWithChildren($account->code, $account->name);
        }

        if ($this->usageSummary($account) !== []) {
            throw AccountHasTransactionsException::forAccount($account->code, $account->name);
        }
    }

    /**
     * Counts every dependent record that blocks an account from being removed.
     *
     * @return array<string, int>
     */
    public function usageSummary(Account $account): array
    {
        $usage = [
            'journal_lines' => $account->journalLines()->count(),
            'bank_statement_lines' => $account->bankStatementLines()->count(),
            'payments' => $account->payments()->count(),
            'expenses' => $account->expensesAsExpense()->count() + $account->expensesAsPayment()->count(),
            'sales_invoices' => $account->salesInvoices()->count(),
            'purchase_bills' => $account->purchaseBills()->count(),
            'credit_notes' => $account->creditNotes()->count(),
            'debit_notes' => $account->debitNotes()->count(),
        ];

        return array_filter($usage);
    }

    /**
     * Identifies the attributes that must stay frozen to preserve accounting integrity.
     *
     * @return array{code: bool, type: bool, opening_balance: bool}
     */
    public function lockedAttributes(Account $account): array
    {
        $hasLedgerHistory = $account->journalLines()->exists();

        return [
            'code' => $account->is_system || $hasLedgerHistory,
            'type' => $account->is_system || $hasLedgerHistory,
            'opening_balance' => $hasLedgerHistory,
        ];
    }

    /**
     * @throws AccountHasTransactionsException
     */
    private function assertParentAssignable(Account $account, ?string $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        $this->assertParentBelongsToBusiness($account->business, $parentId);

        if ($parentId === $account->id || $this->descendantIds($account)->contains($parentId)) {
            throw AccountHasTransactionsException::forLockedAttribute($account->code, 'parent_id');
        }
    }

    private function assertParentBelongsToBusiness(Business $business, string $parentId): void
    {
        $exists = Account::query()
            ->where('business_id', $business->id)
            ->whereKey($parentId)
            ->exists();

        abort_unless($exists, 422);
    }

    /**
     * @return Collection<int, string>
     */
    private function descendantIds(Account $account): Collection
    {
        $descendants = collect();
        $queue = $account->children()->pluck('id')->all();

        while ($queue !== []) {
            $current = array_shift($queue);

            if ($descendants->contains($current)) {
                continue;
            }

            $descendants->push($current);
            $queue = array_merge($queue, Account::query()->where('parent_id', $current)->pluck('id')->all());
        }

        return $descendants;
    }

    public function postOpeningBalances(
        Business $business,
        FinancialYear $financialYear,
        User $user,
        AccountingService $accountingService,
    ): ?Journal {
        if ($financialYear->isLocked()) {
            throw new FinancialYearClosedException;
        }

        $accounts = Account::query()
            ->where('business_id', $business->id)
            ->where('opening_balance', '>', 0)
            ->whereNotNull('opening_balance_side')
            ->whereNotNull('parent_id')
            ->get();

        if ($accounts->isEmpty()) {
            return null;
        }

        $lines = [];
        $capitalAccount = Account::query()
            ->where('business_id', $business->id)
            ->where('code', '3100')
            ->firstOrFail();

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($accounts as $account) {
            if ($account->opening_balance_side === BalanceSide::Debit) {
                $lines[] = new JournalLineData(
                    accountId: $account->id,
                    debitAmount: $account->opening_balance,
                    description: 'Opening balance',
                );
                $totalDebits += $account->opening_balance;
            } else {
                $lines[] = new JournalLineData(
                    accountId: $account->id,
                    creditAmount: $account->opening_balance,
                    description: 'Opening balance',
                );
                $totalCredits += $account->opening_balance;
            }
        }

        $difference = $totalDebits - $totalCredits;

        if ($difference > 0) {
            $lines[] = new JournalLineData(
                accountId: $capitalAccount->id,
                creditAmount: $difference,
                description: 'Opening balance offset',
            );
        } elseif ($difference < 0) {
            $lines[] = new JournalLineData(
                accountId: $capitalAccount->id,
                debitAmount: abs($difference),
                description: 'Opening balance offset',
            );
        }

        return $accountingService->postJournal(
            business: $business,
            financialYear: $financialYear,
            user: $user,
            transactionDate: $financialYear->starts_on,
            description: 'Opening balances',
            lines: $lines,
            sourceType: 'opening_balance',
        );
    }
}
