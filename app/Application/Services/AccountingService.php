<?php

namespace App\Application\Services;

use App\Domain\Accounting\DTOs\JournalLineData;
use App\Domain\Accounting\Enums\JournalStatus;
use App\Domain\Accounting\Exceptions\InvalidJournalLineException;
use App\Domain\Accounting\Exceptions\UnbalancedJournalException;
use App\Models\Account;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public function __construct(
        private FinancialYearService $financialYearService,
        private DocumentNumberService $documentNumberService,
    ) {}

    /**
     * @param  list<JournalLineData>  $lines
     */
    public function postJournal(
        Business $business,
        FinancialYear $financialYear,
        User $user,
        Carbon|string $transactionDate,
        string $description,
        array $lines,
        ?string $sourceType = null,
        ?string $sourceId = null,
        ?Journal $reversalOf = null,
    ): Journal {
        $this->financialYearService->assertWritable($financialYear);
        $this->validateLines($lines, $business);

        return DB::transaction(function () use (
            $business,
            $financialYear,
            $user,
            $transactionDate,
            $description,
            $lines,
            $sourceType,
            $sourceId,
            $reversalOf,
        ): Journal {
            $journal = Journal::query()->create([
                'business_id' => $business->id,
                'financial_year_id' => $financialYear->id,
                'journal_number' => $this->documentNumberService->nextNumber(
                    $business,
                    'journal',
                    'JRN',
                    $financialYear,
                ),
                'transaction_date' => Carbon::parse($transactionDate)->toDateString(),
                'description' => $description,
                'status' => JournalStatus::Posted,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reversal_of_id' => $reversalOf?->id,
                'posted_at' => now(),
                'posted_by' => $user->id,
                'created_by' => $user->id,
            ]);

            foreach ($lines as $index => $line) {
                JournalLine::query()->create([
                    'business_id' => $business->id,
                    'financial_year_id' => $financialYear->id,
                    'journal_id' => $journal->id,
                    'account_id' => $line->accountId,
                    'debit_amount' => $line->debitAmount,
                    'credit_amount' => $line->creditAmount,
                    'description' => $line->description,
                    'line_number' => $index + 1,
                ]);
            }

            $journal->load('lines');

            if (! $journal->isBalanced()) {
                throw new UnbalancedJournalException($journal->totalDebits(), $journal->totalCredits());
            }

            if ($reversalOf !== null) {
                $reversalOf->update([
                    'status' => JournalStatus::Reversed,
                    'reversed_by_id' => $journal->id,
                ]);
            }

            return $journal;
        });
    }

    public function reverseJournal(Journal $journal, User $user, ?string $reason = null): Journal
    {
        if ($journal->status !== JournalStatus::Posted) {
            throw new InvalidJournalLineException('Only posted journals can be reversed.');
        }

        $financialYear = $journal->financialYear()->firstOrFail();
        $this->financialYearService->assertWritable($financialYear);

        $reversalLines = $journal->lines->map(fn (JournalLine $line) => new JournalLineData(
            accountId: $line->account_id,
            debitAmount: $line->credit_amount,
            creditAmount: $line->debit_amount,
            description: $reason ?? 'Reversal of '.$journal->journal_number,
        ))->all();

        return $this->postJournal(
            business: $journal->business,
            financialYear: $financialYear,
            user: $user,
            transactionDate: now(),
            description: $reason ?? 'Reversal of '.$journal->journal_number,
            lines: $reversalLines,
            sourceType: 'journal_reversal',
            sourceId: $journal->id,
            reversalOf: $journal,
        );
    }

    /**
     * @param  list<JournalLineData>  $lines
     */
    public function validateLines(array $lines, Business $business): void
    {
        if (count($lines) < 2) {
            throw new InvalidJournalLineException('A journal must contain at least two lines.');
        }

        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($lines as $line) {
            if (($line->debitAmount > 0 && $line->creditAmount > 0) || ($line->debitAmount === 0 && $line->creditAmount === 0)) {
                throw new InvalidJournalLineException;
            }

            $account = Account::query()
                ->where('business_id', $business->id)
                ->where('id', $line->accountId)
                ->first();

            if ($account === null) {
                throw new InvalidJournalLineException('Account does not belong to this business.');
            }

            if (! $account->isPostable()) {
                throw new InvalidJournalLineException(sprintf('Account %s is not postable.', $account->code));
            }

            $totalDebits += $line->debitAmount;
            $totalCredits += $line->creditAmount;
        }

        if ($totalDebits !== $totalCredits) {
            throw new UnbalancedJournalException($totalDebits, $totalCredits);
        }
    }
}
