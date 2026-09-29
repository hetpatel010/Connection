<?php

namespace App\Application\Services;

use App\Domain\Shared\Exceptions\InvalidDocumentStateException;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Models\Account;
use App\Models\Business;
use App\Models\Expense;
use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private AccountingService $accountingService,
        private DocumentNumberService $documentNumberService,
        private TransactionJournalBuilder $journalBuilder,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createAndConfirm(
        Business $business,
        FinancialYear $financialYear,
        User $user,
        array $attributes,
    ): Expense {
        return DB::transaction(function () use ($business, $financialYear, $user, $attributes): Expense {
            $amount = (int) $attributes['amount'];
            $taxAmount = (int) ($attributes['tax_amount'] ?? 0);

            $expenseAccount = Account::query()
                ->where('business_id', $business->id)
                ->where('id', $attributes['expense_account_id'])
                ->firstOrFail();

            $paymentAccount = Account::query()
                ->where('business_id', $business->id)
                ->where('id', $attributes['payment_account_id'])
                ->firstOrFail();

            $expense = Expense::query()->create([
                'business_id' => $business->id,
                'financial_year_id' => $financialYear->id,
                'expense_number' => $this->documentNumberService->nextNumber($business, 'expense', 'EXP', $financialYear),
                'expense_date' => $attributes['expense_date'],
                'expense_account_id' => $expenseAccount->id,
                'payment_account_id' => $paymentAccount->id,
                'party_id' => $attributes['party_id'] ?? null,
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'description' => $attributes['description'],
                'reference' => $attributes['reference'] ?? null,
                'status' => DocumentStatus::Draft,
                'created_by' => $user->id,
            ]);

            $journal = $this->accountingService->postJournal(
                business: $business,
                financialYear: $financialYear,
                user: $user,
                transactionDate: $expense->expense_date,
                description: 'Expense '.$expense->expense_number,
                lines: $this->journalBuilder->expense($business, $expenseAccount, $paymentAccount, $amount, $taxAmount),
                sourceType: 'expense',
                sourceId: $expense->id,
            );

            $expense->update([
                'status' => DocumentStatus::Confirmed,
                'journal_id' => $journal->id,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            return $expense->fresh(['expenseAccount', 'paymentAccount', 'journal']);
        });
    }

    public function reverse(Expense $expense, User $user, ?string $reason = null): Expense
    {
        if ($expense->status !== DocumentStatus::Confirmed) {
            throw InvalidDocumentStateException::mustBeConfirmed('reversed');
        }

        return DB::transaction(function () use ($expense, $user, $reason): Expense {
            $journal = $expense->journal()->firstOrFail();
            $this->accountingService->reverseJournal($journal, $user, $reason);

            $expense->update(['status' => DocumentStatus::Reversed]);

            return $expense->fresh(['expenseAccount', 'paymentAccount', 'journal']);
        });
    }
}
