<?php

namespace App\Application\Services;

use App\Domain\Shared\Exceptions\InvalidDocumentStateException;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\PaymentMode;
use App\Domain\Transactions\Enums\PaymentType;
use App\Models\Account;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private AccountingService $accountingService,
        private DocumentNumberService $documentNumberService,
        private TransactionJournalBuilder $journalBuilder,
        private AccountLookupService $accounts,
        private PaymentAllocationService $allocationService,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createAndConfirm(
        Business $business,
        FinancialYear $financialYear,
        User $user,
        array $attributes,
    ): Payment {
        return DB::transaction(function () use ($business, $financialYear, $user, $attributes): Payment {
            $paymentType = PaymentType::from($attributes['payment_type']);
            $paymentMode = PaymentMode::from($attributes['payment_mode'] ?? PaymentMode::Cash->value);
            $amount = (int) $attributes['amount'];
            $allocations = $this->normalizeAllocationInput($attributes);

            $validatedAllocations = $this->allocationService->validate(
                business: $business,
                paymentType: $paymentType,
                paymentAmount: $amount,
                partyId: $attributes['party_id'] ?? null,
                allocations: $allocations,
            );

            $paymentAccount = $this->resolvePaymentAccount($business, $attributes['account_id'] ?? null, $paymentMode);

            $payment = Payment::query()->create([
                'business_id' => $business->id,
                'financial_year_id' => $financialYear->id,
                'payment_number' => $this->documentNumberService->nextNumber(
                    $business,
                    $paymentType->value,
                    $paymentType === PaymentType::PaymentIn ? 'PIN' : 'POUT',
                    $financialYear,
                ),
                'payment_type' => $paymentType,
                'party_id' => $attributes['party_id'] ?? null,
                'payment_date' => $attributes['payment_date'],
                'payment_mode' => $paymentMode,
                'account_id' => $paymentAccount->id,
                'amount' => $amount,
                'reference' => $attributes['reference'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'status' => DocumentStatus::Draft,
                'created_by' => $user->id,
            ]);

            $journal = $this->accountingService->postJournal(
                business: $business,
                financialYear: $financialYear,
                user: $user,
                transactionDate: $payment->payment_date,
                description: $paymentType->label().' '.$payment->payment_number,
                lines: $this->journalBuilder->payment($business, $paymentType, $amount, $paymentAccount),
                sourceType: $paymentType->value,
                sourceId: $payment->id,
            );

            $payment->update([
                'status' => DocumentStatus::Confirmed,
                'journal_id' => $journal->id,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            $this->allocationService->persist($business, $payment->id, $validatedAllocations);

            return $payment->fresh(['party', 'journal', 'allocations.allocatable']);
        });
    }

    public function reverse(Payment $payment, User $user, ?string $reason = null): Payment
    {
        if ($payment->status !== DocumentStatus::Confirmed) {
            throw InvalidDocumentStateException::mustBeConfirmed('reversed');
        }

        return DB::transaction(function () use ($payment, $user, $reason): Payment {
            $journal = $payment->journal()->firstOrFail();
            $this->accountingService->reverseJournal($journal, $user, $reason);

            $payment->update(['status' => DocumentStatus::Reversed]);

            return $payment->fresh(['party', 'journal', 'allocations']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<array{allocatable_type: string, allocatable_id: string, allocated_amount: int}>
     */
    private function normalizeAllocationInput(array $attributes): array
    {
        if (! empty($attributes['allocations']) && is_array($attributes['allocations'])) {
            return collect($attributes['allocations'])
                ->map(fn (array $allocation): array => [
                    'allocatable_type' => $allocation['allocatable_type'],
                    'allocatable_id' => $allocation['allocatable_id'],
                    'allocated_amount' => (int) $allocation['allocated_amount'],
                ])
                ->all();
        }

        $legacy = [];

        if (! empty($attributes['sales_invoice_id'])) {
            $legacy[] = [
                'allocatable_type' => 'sales_invoice',
                'allocatable_id' => $attributes['sales_invoice_id'],
                'allocated_amount' => (int) $attributes['amount'],
            ];
        }

        if (! empty($attributes['purchase_bill_id'])) {
            $legacy[] = [
                'allocatable_type' => 'purchase_bill',
                'allocatable_id' => $attributes['purchase_bill_id'],
                'allocated_amount' => (int) $attributes['amount'],
            ];
        }

        return $legacy;
    }

    private function resolvePaymentAccount(Business $business, ?string $accountId, PaymentMode $mode): Account
    {
        if ($accountId !== null) {
            return Account::query()
                ->where('business_id', $business->id)
                ->where('id', $accountId)
                ->firstOrFail();
        }

        return $this->accounts->byCode($business, $mode === PaymentMode::Bank ? '1200' : '1100');
    }
}
