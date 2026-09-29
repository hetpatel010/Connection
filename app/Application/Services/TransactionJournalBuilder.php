<?php

namespace App\Application\Services;

use App\Domain\Accounting\DTOs\JournalLineData;
use App\Domain\Transactions\Enums\PaymentType;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\Account;
use App\Models\Business;

class TransactionJournalBuilder
{
    public function __construct(
        private AccountLookupService $accounts,
    ) {}

    /**
     * @return list<JournalLineData>
     */
    public function salesInvoice(Business $business, int $subtotal, int $tax, int $total, SettlementMode $settlementMode, ?Account $paymentAccount): array
    {
        $sales = $this->accounts->byCode($business, '4100');
        $lines = [
            new JournalLineData(
                accountId: $this->debitAccountForSales($business, $settlementMode, $paymentAccount)->id,
                debitAmount: $total,
                description: 'Sales invoice',
            ),
            new JournalLineData(
                accountId: $sales->id,
                creditAmount: $subtotal,
                description: 'Sales revenue',
            ),
        ];

        if ($tax > 0) {
            $lines[] = new JournalLineData(
                accountId: $this->accounts->byCode($business, '2200')->id,
                creditAmount: $tax,
                description: 'GST output',
            );
        }

        return $lines;
    }

    /**
     * @return list<JournalLineData>
     */
    public function purchaseBill(Business $business, int $subtotal, int $tax, int $total, SettlementMode $settlementMode, ?Account $paymentAccount): array
    {
        $purchase = $this->accounts->byCode($business, '5100');
        $lines = [
            new JournalLineData(
                accountId: $purchase->id,
                debitAmount: $subtotal,
                description: 'Purchase',
            ),
        ];

        if ($tax > 0) {
            $lines[] = new JournalLineData(
                accountId: $this->accounts->byCode($business, '1500')->id,
                debitAmount: $tax,
                description: 'GST input',
            );
        }

        $lines[] = new JournalLineData(
            accountId: $this->creditAccountForPurchase($business, $settlementMode, $paymentAccount)->id,
            creditAmount: $total,
            description: 'Purchase bill',
        );

        return $lines;
    }

    /**
     * @return list<JournalLineData>
     */
    public function creditNote(Business $business, int $subtotal, int $tax, int $total, SettlementMode $settlementMode, ?Account $paymentAccount): array
    {
        $sales = $this->accounts->byCode($business, '4100');
        $lines = [
            new JournalLineData(
                accountId: $this->debitAccountForSales($business, $settlementMode, $paymentAccount)->id,
                creditAmount: $total,
                description: 'Credit note',
            ),
            new JournalLineData(
                accountId: $sales->id,
                debitAmount: $subtotal,
                description: 'Sales return',
            ),
        ];

        if ($tax > 0) {
            $lines[] = new JournalLineData(
                accountId: $this->accounts->byCode($business, '2200')->id,
                debitAmount: $tax,
                description: 'GST output reversal',
            );
        }

        return $lines;
    }

    /**
     * @return list<JournalLineData>
     */
    public function debitNote(Business $business, int $subtotal, int $tax, int $total, SettlementMode $settlementMode, ?Account $paymentAccount): array
    {
        $purchase = $this->accounts->byCode($business, '5100');
        $lines = [
            new JournalLineData(
                accountId: $purchase->id,
                creditAmount: $subtotal,
                description: 'Purchase return',
            ),
        ];

        if ($tax > 0) {
            $lines[] = new JournalLineData(
                accountId: $this->accounts->byCode($business, '1500')->id,
                creditAmount: $tax,
                description: 'GST input reversal',
            );
        }

        $lines[] = new JournalLineData(
            accountId: $this->creditAccountForPurchase($business, $settlementMode, $paymentAccount)->id,
            debitAmount: $total,
            description: 'Debit note',
        );

        return $lines;
    }

    /**
     * @return list<JournalLineData>
     */
    public function payment(Business $business, PaymentType $paymentType, int $amount, Account $paymentAccount): array
    {
        if ($paymentType === PaymentType::PaymentIn) {
            return [
                new JournalLineData(
                    accountId: $paymentAccount->id,
                    debitAmount: $amount,
                    description: 'Payment received',
                ),
                new JournalLineData(
                    accountId: $this->accounts->byCode($business, '1300')->id,
                    creditAmount: $amount,
                    description: 'Accounts receivable',
                ),
            ];
        }

        return [
            new JournalLineData(
                accountId: $this->accounts->byCode($business, '2100')->id,
                debitAmount: $amount,
                description: 'Accounts payable',
            ),
            new JournalLineData(
                accountId: $paymentAccount->id,
                creditAmount: $amount,
                description: 'Payment made',
            ),
        ];
    }

    /**
     * @return list<JournalLineData>
     */
    public function expense(Business $business, Account $expenseAccount, Account $paymentAccount, int $amount, int $tax): array
    {
        $lines = [
            new JournalLineData(
                accountId: $expenseAccount->id,
                debitAmount: $amount,
                description: 'Expense',
            ),
        ];

        if ($tax > 0) {
            $lines[] = new JournalLineData(
                accountId: $this->accounts->byCode($business, '1500')->id,
                debitAmount: $tax,
                description: 'GST input',
            );
        }

        $lines[] = new JournalLineData(
            accountId: $paymentAccount->id,
            creditAmount: $amount + $tax,
            description: 'Expense payment',
        );

        return $lines;
    }

    private function debitAccountForSales(Business $business, SettlementMode $settlementMode, ?Account $paymentAccount): Account
    {
        if ($settlementMode === SettlementMode::Credit) {
            return $this->accounts->byCode($business, '1300');
        }

        return $paymentAccount ?? $this->accounts->byCode($business, '1100');
    }

    private function creditAccountForPurchase(Business $business, SettlementMode $settlementMode, ?Account $paymentAccount): Account
    {
        if ($settlementMode === SettlementMode::Credit) {
            return $this->accounts->byCode($business, '2100');
        }

        return $paymentAccount ?? $this->accounts->byCode($business, '1100');
    }
}
