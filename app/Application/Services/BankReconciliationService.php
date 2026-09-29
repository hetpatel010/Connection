<?php

namespace App\Application\Services;

use App\Domain\Shared\Exceptions\DomainException;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\PaymentMode;
use App\Models\Account;
use App\Models\BankStatementLine;
use App\Models\Business;
use App\Models\Payment;
use Illuminate\Support\Collection;

class BankReconciliationService
{
    /**
     * @param  array{
     *     transaction_date: string,
     *     description?: string|null,
     *     reference?: string|null,
     *     amount: int
     * }  $attributes
     */
    public function addStatementLine(Business $business, Account $account, array $attributes): BankStatementLine
    {
        if ($account->business_id !== $business->id) {
            throw new DomainException('Account does not belong to this business.');
        }

        return BankStatementLine::query()->create([
            'business_id' => $business->id,
            'account_id' => $account->id,
            'transaction_date' => $attributes['transaction_date'],
            'description' => $attributes['description'] ?? null,
            'reference' => $attributes['reference'] ?? null,
            'amount' => $attributes['amount'],
        ]);
    }

    public function matchToPayment(BankStatementLine $line, Payment $payment): BankStatementLine
    {
        if ($line->matched_payment_id !== null) {
            throw new DomainException('Statement line is already matched.');
        }

        if ($line->business_id !== $payment->business_id) {
            throw new DomainException('Statement line and payment belong to different businesses.');
        }

        if ($line->account_id !== $payment->account_id) {
            throw new DomainException('Statement line and payment use different accounts.');
        }

        if ($payment->payment_mode !== PaymentMode::Bank || ! $payment->status->isConfirmed()) {
            throw new DomainException('Only confirmed bank payments can be matched.');
        }

        $alreadyMatched = BankStatementLine::query()
            ->where('matched_payment_id', $payment->id)
            ->exists();

        if ($alreadyMatched) {
            throw new DomainException('Payment is already matched to a statement line.');
        }

        $line->update([
            'matched_payment_id' => $payment->id,
            'matched_at' => now(),
        ]);

        return $line->fresh(['matchedPayment']);
    }

    /**
     * @return Collection<int, BankStatementLine>
     */
    public function unmatchedLines(Business $business, Account $account): Collection
    {
        return BankStatementLine::query()
            ->where('business_id', $business->id)
            ->where('account_id', $account->id)
            ->whereNull('matched_payment_id')
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return Collection<int, Payment>
     */
    public function unmatchedPayments(Business $business, Account $account): Collection
    {
        $matchedPaymentIds = BankStatementLine::query()
            ->where('business_id', $business->id)
            ->where('account_id', $account->id)
            ->whereNotNull('matched_payment_id')
            ->pluck('matched_payment_id');

        return Payment::query()
            ->where('business_id', $business->id)
            ->where('account_id', $account->id)
            ->where('payment_mode', PaymentMode::Bank)
            ->where('status', DocumentStatus::Confirmed)
            ->when($matchedPaymentIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $matchedPaymentIds))
            ->with('party')
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->get();
    }
}
