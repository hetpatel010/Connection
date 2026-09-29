<?php

namespace App\Application\Services;

use App\Domain\Shared\Exceptions\InvalidPaymentAllocationException;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\PaymentType;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\Business;
use App\Models\PaymentAllocation;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PaymentAllocationService
{
    public function allocatedAmount(Model $document): int
    {
        $type = $this->morphTypeFor($document);

        return (int) PaymentAllocation::query()
            ->where('allocatable_type', $type)
            ->where('allocatable_id', $document->getKey())
            ->whereHas('payment', fn ($query) => $query->where('status', DocumentStatus::Confirmed))
            ->sum('allocated_amount');
    }

    public function outstandingBalance(Model $document): int
    {
        if (! $document instanceof SalesInvoice && ! $document instanceof PurchaseBill) {
            return 0;
        }

        if ($document->status !== DocumentStatus::Confirmed || $document->settlement_mode !== SettlementMode::Credit) {
            return 0;
        }

        return max(0, $document->total_amount - $this->allocatedAmount($document));
    }

    /**
     * @return Collection<int, SalesInvoice>
     */
    public function openSalesInvoices(Business $business, string $partyId): Collection
    {
        return SalesInvoice::query()
            ->where('business_id', $business->id)
            ->where('party_id', $partyId)
            ->where('status', DocumentStatus::Confirmed)
            ->where('settlement_mode', SettlementMode::Credit)
            ->orderBy('invoice_date')
            ->orderBy('invoice_number')
            ->get()
            ->filter(fn (SalesInvoice $invoice): bool => $this->outstandingBalance($invoice) > 0)
            ->values();
    }

    /**
     * @return Collection<int, PurchaseBill>
     */
    public function openPurchaseBills(Business $business, string $partyId): Collection
    {
        return PurchaseBill::query()
            ->where('business_id', $business->id)
            ->where('party_id', $partyId)
            ->where('status', DocumentStatus::Confirmed)
            ->where('settlement_mode', SettlementMode::Credit)
            ->orderBy('bill_date')
            ->orderBy('bill_number')
            ->get()
            ->filter(fn (PurchaseBill $bill): bool => $this->outstandingBalance($bill) > 0)
            ->values();
    }

    /**
     * @param  list<string>  $documentIds
     * @return array<string, int>
     */
    public function allocatedAmountsFor(string $allocatableType, array $documentIds): array
    {
        if ($documentIds === []) {
            return [];
        }

        return PaymentAllocation::query()
            ->where('allocatable_type', $allocatableType)
            ->whereIn('allocatable_id', $documentIds)
            ->whereHas('payment', fn ($query) => $query->where('status', DocumentStatus::Confirmed))
            ->groupBy('allocatable_id')
            ->selectRaw('allocatable_id, sum(allocated_amount) as total')
            ->pluck('total', 'allocatable_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * @param  list<array{allocatable_type: string, allocatable_id: string, allocated_amount: int}>  $allocations
     * @return list<array{allocatable_type: string, allocatable_id: string, allocated_amount: int, document: SalesInvoice|PurchaseBill}>
     */
    public function validate(Business $business, PaymentType $paymentType, int $paymentAmount, ?string $partyId, array $allocations): array
    {
        if ($allocations === []) {
            return [];
        }

        if ($partyId === null || $partyId === '') {
            throw InvalidPaymentAllocationException::partyRequired();
        }

        $normalized = [];
        $totalAllocated = 0;

        foreach ($allocations as $allocation) {
            $amount = (int) $allocation['allocated_amount'];

            if ($amount <= 0) {
                continue;
            }

            $document = $this->resolveDocument(
                $business,
                $paymentType,
                $allocation['allocatable_type'],
                $allocation['allocatable_id'],
            );

            if ($document->party_id !== $partyId) {
                throw InvalidPaymentAllocationException::partyMismatch($this->documentNumber($document));
            }

            $outstanding = $this->outstandingBalance($document);

            if ($amount > $outstanding) {
                throw InvalidPaymentAllocationException::exceedsOutstanding(
                    $this->documentNumber($document),
                    $amount,
                    $outstanding,
                );
            }

            $totalAllocated += $amount;
            $normalized[] = [
                'allocatable_type' => $allocation['allocatable_type'],
                'allocatable_id' => $allocation['allocatable_id'],
                'allocated_amount' => $amount,
                'document' => $document,
            ];
        }

        if ($totalAllocated > $paymentAmount) {
            throw InvalidPaymentAllocationException::exceedsPaymentAmount($totalAllocated, $paymentAmount);
        }

        return $normalized;
    }

    /**
     * @param  list<array{allocatable_type: string, allocatable_id: string, allocated_amount: int, document: SalesInvoice|PurchaseBill}>  $validatedAllocations
     */
    public function persist(Business $business, string $paymentId, array $validatedAllocations): void
    {
        foreach ($validatedAllocations as $allocation) {
            PaymentAllocation::query()->create([
                'business_id' => $business->id,
                'payment_id' => $paymentId,
                'allocatable_type' => $allocation['allocatable_type'],
                'allocatable_id' => $allocation['allocatable_id'],
                'allocated_amount' => $allocation['allocated_amount'],
            ]);
        }
    }

    private function resolveDocument(
        Business $business,
        PaymentType $paymentType,
        string $allocatableType,
        string $allocatableId,
    ): SalesInvoice|PurchaseBill {
        if ($paymentType === PaymentType::PaymentIn && $allocatableType !== 'sales_invoice') {
            throw InvalidPaymentAllocationException::invalidDocumentType($allocatableType);
        }

        if ($paymentType === PaymentType::PaymentOut && $allocatableType !== 'purchase_bill') {
            throw InvalidPaymentAllocationException::invalidDocumentType($allocatableType);
        }

        $document = match ($allocatableType) {
            'sales_invoice' => SalesInvoice::query()
                ->where('business_id', $business->id)
                ->findOrFail($allocatableId),
            'purchase_bill' => PurchaseBill::query()
                ->where('business_id', $business->id)
                ->findOrFail($allocatableId),
            default => throw InvalidPaymentAllocationException::invalidDocumentType($allocatableType),
        };

        if ($document->status !== DocumentStatus::Confirmed || $document->settlement_mode !== SettlementMode::Credit) {
            throw InvalidPaymentAllocationException::invalidDocumentType($this->documentNumber($document));
        }

        return $document;
    }

    private function morphTypeFor(Model $document): string
    {
        return match (true) {
            $document instanceof SalesInvoice => 'sales_invoice',
            $document instanceof PurchaseBill => 'purchase_bill',
            default => $document->getMorphClass(),
        };
    }

    private function documentNumber(SalesInvoice|PurchaseBill $document): string
    {
        return $document instanceof SalesInvoice
            ? $document->invoice_number
            : $document->bill_number;
    }
}
