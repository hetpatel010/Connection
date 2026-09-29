<?php

namespace App\Application\Services;

use App\Domain\Shared\Exceptions\InvalidDocumentStateException;
use App\Domain\Transactions\Enums\BillingType;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Domain\Transactions\LineTotals;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseBillService
{
    public function __construct(
        private AccountingService $accountingService,
        private DocumentNumberService $documentNumberService,
        private TransactionJournalBuilder $journalBuilder,
        private InventoryService $inventoryService,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $lines
     */
    public function createDraft(
        Business $business,
        FinancialYear $financialYear,
        User $user,
        array $attributes,
        array $lines,
    ): PurchaseBill {
        return DB::transaction(function () use ($business, $financialYear, $user, $attributes, $lines): PurchaseBill {
            $party = Party::query()->findOrFail($attributes['party_id']);
            abort_unless($party->party_type->isSupplier(), 422, 'Party must be a supplier.');

            $billing = $this->resolveBillingAttributes($attributes);

            $bill = PurchaseBill::query()->create([
                'business_id' => $business->id,
                'financial_year_id' => $financialYear->id,
                'bill_number' => $this->documentNumberService->nextNumber($business, 'purchase_bill', 'BILL', $financialYear),
                'party_id' => $party->id,
                'bill_date' => $attributes['bill_date'],
                'due_date' => $attributes['due_date'] ?? null,
                'settlement_mode' => $attributes['settlement_mode'] ?? SettlementMode::Credit,
                'payment_account_id' => $attributes['payment_account_id'] ?? null,
                'status' => DocumentStatus::Draft,
                'billing_type' => $billing['billing_type'],
                'document_template' => $billing['document_template'],
                'notes' => $attributes['notes'] ?? null,
                'reference' => $attributes['reference'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncLines($bill, $lines, $billing['billing_type']);

            return $bill->fresh(['lines', 'party']);
        });
    }

    public function confirm(PurchaseBill $bill, User $user): PurchaseBill
    {
        if ($bill->status !== DocumentStatus::Draft) {
            throw InvalidDocumentStateException::mustBeDraft('confirmed');
        }

        $bill->load('lines', 'paymentAccount');
        abort_if($bill->lines->isEmpty(), 422, 'Bill must have at least one line.');

        return DB::transaction(function () use ($bill, $user): PurchaseBill {
            $journal = $this->accountingService->postJournal(
                business: $bill->business,
                financialYear: $bill->financialYear,
                user: $user,
                transactionDate: $bill->bill_date,
                description: 'Purchase bill '.$bill->bill_number,
                lines: $this->journalBuilder->purchaseBill(
                    $bill->business,
                    $bill->subtotal_amount,
                    $bill->tax_amount,
                    $bill->total_amount,
                    $bill->settlement_mode,
                    $bill->paymentAccount,
                ),
                sourceType: 'purchase_bill',
                sourceId: $bill->id,
            );

            $bill->update([
                'status' => DocumentStatus::Confirmed,
                'journal_id' => $journal->id,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            $this->inventoryService->recordPurchase($bill, $user);

            return $bill->fresh(['lines', 'party', 'journal']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $lines
     */
    public function updateDraft(PurchaseBill $bill, User $user, array $attributes, array $lines): PurchaseBill
    {
        if (! $bill->status->isEditable()) {
            throw InvalidDocumentStateException::mustBeDraft('updated');
        }

        return DB::transaction(function () use ($bill, $attributes, $lines): PurchaseBill {
            $party = Party::query()->findOrFail($attributes['party_id']);
            abort_unless($party->party_type->isSupplier(), 422, 'Party must be a supplier.');

            $billing = $this->resolveBillingAttributes($attributes);

            $bill->update([
                'party_id' => $party->id,
                'bill_date' => $attributes['bill_date'],
                'due_date' => $attributes['due_date'] ?? null,
                'settlement_mode' => $attributes['settlement_mode'] ?? SettlementMode::Credit,
                'payment_account_id' => $attributes['payment_account_id'] ?? null,
                'billing_type' => $billing['billing_type'],
                'document_template' => $billing['document_template'],
                'notes' => $attributes['notes'] ?? null,
                'reference' => $attributes['reference'] ?? null,
            ]);

            $this->syncLines($bill, $lines, $billing['billing_type']);

            return $bill->fresh(['lines', 'party']);
        });
    }

    public function deleteDraft(PurchaseBill $bill, User $user): void
    {
        if (! $bill->status->isEditable()) {
            throw InvalidDocumentStateException::mustBeDraft('deleted');
        }

        DB::transaction(function () use ($bill): void {
            $bill->lines()->delete();
            $bill->delete();
        });
    }

    public function reverse(PurchaseBill $bill, User $user, ?string $reason = null): PurchaseBill
    {
        if ($bill->status !== DocumentStatus::Confirmed) {
            throw InvalidDocumentStateException::mustBeConfirmed('reversed');
        }

        return DB::transaction(function () use ($bill, $user, $reason): PurchaseBill {
            $journal = $bill->journal()->firstOrFail();
            $this->accountingService->reverseJournal($journal, $user, $reason);

            $bill->update(['status' => DocumentStatus::Reversed]);

            $this->inventoryService->reversePurchase($bill, $user);

            return $bill->fresh(['lines', 'party', 'journal']);
        });
    }

    public function cancel(PurchaseBill $bill, User $user, ?string $reason = null): PurchaseBill
    {
        if ($bill->status !== DocumentStatus::Confirmed) {
            throw InvalidDocumentStateException::mustBeConfirmed('cancelled');
        }

        return DB::transaction(function () use ($bill, $user, $reason): PurchaseBill {
            $journal = $bill->journal()->firstOrFail();
            $this->accountingService->reverseJournal($journal, $user, $reason);

            $bill->update(['status' => DocumentStatus::Cancelled]);

            $this->inventoryService->reversePurchase($bill, $user);

            return $bill->fresh(['lines', 'party', 'journal']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(PurchaseBill $bill, array $lines, BillingType $billingType): void
    {
        $bill->lines()->delete();

        $preparedLines = collect($lines)->map(function (array $line) use ($billingType): array {
            $product = isset($line['product_id']) ? Product::query()->find($line['product_id']) : null;

            return [
                'quantity' => (float) ($line['quantity'] ?? 1),
                'unit_price' => (int) ($line['unit_price'] ?? $product?->purchase_price ?? 0),
                'tax_rate' => $billingType->isGst()
                    ? (float) ($line['tax_rate'] ?? $product?->tax_rate ?? 0)
                    : 0.0,
            ];
        })->all();

        $totals = LineTotals::fromLines($preparedLines);

        foreach ($lines as $index => $line) {
            $computed = $totals['lines'][$index];
            $product = isset($line['product_id']) ? Product::query()->find($line['product_id']) : null;

            PurchaseBillLine::query()->create([
                'business_id' => $bill->business_id,
                'purchase_bill_id' => $bill->id,
                'line_number' => $index + 1,
                'product_id' => $product?->id,
                'description' => $line['description'] ?? $product?->name ?? 'Item',
                'quantity' => $computed['quantity'],
                'unit_price' => $computed['unit_price'],
                'tax_rate' => $computed['tax_rate'],
                'tax_amount' => $computed['tax'],
                'line_total' => $computed['total'],
                'hsn_sac' => $line['hsn_sac'] ?? $product?->hsn_sac,
            ]);
        }

        $bill->update([
            'subtotal_amount' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'total_amount' => $totals['total'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{billing_type: BillingType, document_template: ?string}
     */
    private function resolveBillingAttributes(array $attributes): array
    {
        $billingType = $attributes['billing_type'] ?? BillingType::Gst;

        if (! $billingType instanceof BillingType) {
            $billingType = BillingType::from($billingType);
        }

        $documentTemplate = $attributes['document_template'] ?? null;

        if ($documentTemplate === '') {
            $documentTemplate = null;
        }

        return [
            'billing_type' => $billingType,
            'document_template' => $documentTemplate,
        ];
    }
}
