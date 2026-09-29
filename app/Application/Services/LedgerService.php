<?php

namespace App\Application\Services;

use App\Domain\Accounting\Enums\BalanceSide;
use App\Domain\Accounting\Enums\JournalStatus;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\JournalLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LedgerService
{
    /**
     * @return Collection<int, array{
     *     date: string,
     *     journal_number: string,
     *     description: string|null,
     *     debit_amount: int,
     *     credit_amount: int,
     *     running_balance: int,
     *     balance_side: BalanceSide
     * }>
     */
    public function accountLedger(
        Account $account,
        FinancialYear $financialYear,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): Collection {
        $query = JournalLine::query()
            ->with(['journal'])
            ->where('account_id', $account->id)
            ->where('financial_year_id', $financialYear->id)
            ->whereHas('journal', fn ($builder) => $builder->where('status', JournalStatus::Posted));

        if ($from !== null) {
            $query->whereHas('journal', fn ($builder) => $builder->whereDate('transaction_date', '>=', $from));
        }

        if ($to !== null) {
            $query->whereHas('journal', fn ($builder) => $builder->whereDate('transaction_date', '<=', $to));
        }

        $lines = $query
            ->get()
            ->sortBy([
                fn (JournalLine $line) => $line->journal->transaction_date->format('Y-m-d'),
                fn (JournalLine $line) => $line->journal->journal_number,
                fn (JournalLine $line) => $line->line_number,
            ])
            ->values();

        $runningBalance = 0;
        $normalSide = $account->type->normalBalanceSide();

        return $lines->map(function (JournalLine $line) use (&$runningBalance, $normalSide) {
            $movement = $line->debit_amount - $line->credit_amount;
            $runningBalance += $movement;

            return [
                'date' => $line->journal->transaction_date->toDateString(),
                'journal_number' => $line->journal->journal_number,
                'description' => $line->description ?? $line->journal->description,
                'debit_amount' => $line->debit_amount,
                'credit_amount' => $line->credit_amount,
                'running_balance' => abs($runningBalance),
                'balance_side' => $runningBalance === 0
                    ? $normalSide
                    : ($runningBalance > 0 ? BalanceSide::Debit : BalanceSide::Credit),
            ];
        });
    }

    public function accountBalance(Account $account, FinancialYear $financialYear, ?Carbon $asOf = null): int
    {
        $query = JournalLine::query()
            ->where('account_id', $account->id)
            ->where('financial_year_id', $financialYear->id)
            ->whereHas('journal', fn ($builder) => $builder->where('status', JournalStatus::Posted));

        if ($asOf !== null) {
            $query->whereHas('journal', fn ($builder) => $builder->whereDate('transaction_date', '<=', $asOf));
        }

        $totals = $query
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debits, COALESCE(SUM(credit_amount), 0) as total_credits')
            ->first();

        return (int) $totals->total_debits - (int) $totals->total_credits;
    }

    public function signedBalance(Account $account, FinancialYear $financialYear, ?Carbon $asOf = null): array
    {
        $rawBalance = $this->accountBalance($account, $financialYear, $asOf);

        return [
            'amount' => abs($rawBalance),
            'side' => $rawBalance === 0
                ? $account->type->normalBalanceSide()
                : ($rawBalance > 0 ? BalanceSide::Debit : BalanceSide::Credit),
        ];
    }

    /**
     * @return array{debits: int, credits: int}
     */
    public function periodTotals(
        Account $account,
        FinancialYear $financialYear,
        Carbon $from,
        Carbon $to,
    ): array {
        $totals = JournalLine::query()
            ->where('account_id', $account->id)
            ->where('financial_year_id', $financialYear->id)
            ->whereHas('journal', fn ($builder) => $builder
                ->where('status', JournalStatus::Posted)
                ->whereDate('transaction_date', '>=', $from)
                ->whereDate('transaction_date', '<=', $to))
            ->selectRaw('COALESCE(SUM(debit_amount), 0) as total_debits, COALESCE(SUM(credit_amount), 0) as total_credits')
            ->first();

        return [
            'debits' => (int) $totals->total_debits,
            'credits' => (int) $totals->total_credits,
        ];
    }
}
