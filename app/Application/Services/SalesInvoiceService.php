<?php

namespace App\Application\Services;

use App\Domain\Products\GstRate;
use App\Domain\Shared\Exceptions\InvalidDocumentStateException;
use App\Domain\Shared\Exceptions\InvalidGstRateException;
use App\Domain\Transactions\Enums\BillingType;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Domain\Transactions\LineTotals;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SalesInvoiceService
{
    public function __construct(
        private AccountingService $accountingService,
        private DocumentNumberService $documentNumberService,
        private InvoiceTemplateService $invoiceTemplateService,
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
    ): SalesInvoice {
        return DB::transaction(function () use ($business, $financialYear, $user, $attributes, $lines): SalesInvoice {
            $party = Party::query()->findOrFail($attributes['party_id']);
            abort_unless($party->party_type->isCustomer(), 422, 'Party must be a customer.');

            $billing = $this->resolveBillingAttributes($attributes, $business);

            $invoice = SalesInvoice::query()->create([
                'business_id' => $business->id,
                'financial_year_id' => $financialYear->id,
                'invoice_number' => $this->documentNumberService->nextNumber($business, 'sales_invoice', 'INV', $financialYear),
                'party_id' => $party->id,
                'invoice_date' => $attributes['invoice_date'],
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

            $this->syncLines($invoice, $lines, $billing['billing_type']);

            return $invoice->fresh(['lines', 'party']);
        });
    }

    public function confirm(SalesInvoice $invoice, User $user): SalesInvoice
    {
        if ($invoice->status !== DocumentStatus::Draft) {
            throw InvalidDocumentStateException::mustBeDraft('confirmed');
        }

        $invoice->load('lines', 'paymentAccount');
        abort_if($invoice->lines->isEmpty(), 422, 'Invoice must have at least one line.');

        return DB::transaction(function () use ($invoice, $user): SalesInvoice {
            $journal = $this->accountingService->postJournal(
                business: $invoice->business,
                financialYear: $invoice->financialYear,
                user: $user,
                transactionDate: $invoice->invoice_date,
                description: 'Sales invoice '.$invoice->invoice_number,
                lines: $this->journalBuilder->salesInvoice(
                    $invoice->business,
                    $invoice->subtotal_amount,
                    $invoice->tax_amount,
                    $invoice->total_amount,
                    $invoice->settlement_mode,
                    $invoice->paymentAccount,
                ),
                sourceType: 'sales_invoice',
                sourceId: $invoice->id,
            );

            $invoice->update([
                'status' => DocumentStatus::Confirmed,
                'journal_id' => $journal->id,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            $this->inventoryService->recordSale($invoice, $user);

            return $invoice->fresh(['lines', 'party', 'journal']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $lines
     */
    public function updateDraft(SalesInvoice $invoice, User $user, array $attributes, array $lines): SalesInvoice
    {
        if (! $invoice->status->isEditable()) {
            throw InvalidDocumentStateException::mustBeDraft('updated');
        }

        return DB::transaction(function () use ($invoice, $attributes, $lines): SalesInvoice {
            $party = Party::query()->findOrFail($attributes['party_id']);
            abort_unless($party->party_type->isCustomer(), 422, 'Party must be a customer.');

            $billing = $this->resolveBillingAttributes($attributes);

            $invoice->update([
                'party_id' => $party->id,
                'invoice_date' => $attributes['invoice_date'],
                'due_date' => $attributes['due_date'] ?? null,
                'settlement_mode' => $attributes['settlement_mode'] ?? SettlementMode::Credit,
                'payment_account_id' => $attributes['payment_account_id'] ?? null,
                'billing_type' => $billing['billing_type'],
                'document_template' => $billing['document_template'],
                'notes' => $attributes['notes'] ?? null,
                'reference' => $attributes['reference'] ?? null,
            ]);

            $this->syncLines($invoice, $lines, $billing['billing_type']);

            return $invoice->fresh(['lines', 'party']);
        });
    }

    public function deleteDraft(SalesInvoice $invoice, User $user): void
    {
        if (! $invoice->status->isEditable()) {
            throw InvalidDocumentStateException::mustBeDraft('deleted');
        }

        DB::transaction(function () use ($invoice): void {
            $invoice->lines()->delete();
            $invoice->delete();
        });
    }

    public function reverse(SalesInvoice $invoice, User $user, ?string $reason = null): SalesInvoice
    {
        if ($invoice->status !== DocumentStatus::Confirmed) {
            throw InvalidDocumentStateException::mustBeConfirmed('reversed');
        }

        return DB::transaction(function () use ($invoice, $user, $reason): SalesInvoice {
            $journal = $invoice->journal()->firstOrFail();
            $this->accountingService->reverseJournal($journal, $user, $reason);

            $invoice->update(['status' => DocumentStatus::Reversed]);

            $this->inventoryService->reverseSale($invoice, $user);

            return $invoice->fresh(['lines', 'party', 'journal']);
        });
    }

    public function cancel(SalesInvoice $invoice, User $user, ?string $reason = null): SalesInvoice
    {
        if ($invoice->status !== DocumentStatus::Confirmed) {
            throw InvalidDocumentStateException::mustBeConfirmed('cancelled');
        }

        return DB::transaction(function () use ($invoice, $user, $reason): SalesInvoice {
            $journal = $invoice->journal()->firstOrFail();
            $this->accountingService->reverseJournal($journal, $user, $reason);

            $invoice->update(['status' => DocumentStatus::Cancelled]);

            $this->inventoryService->reverseSale($invoice, $user);

            return $invoice->fresh(['lines', 'party', 'journal']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(SalesInvoice $invoice, array $lines, BillingType $billingType): void
    {
        $invoice->lines()->delete();

        $products = $this->productsForLines($lines);

        $preparedLines = [];
        $resolvedLines = [];

        foreach (array_values($lines) as $line) {
            $product = $products[(string) ($line['product_id'] ?? '')] ?? null;

            $preparedLines[] = [
                'quantity' => (float) ($line['quantity'] ?? 1),
                'unit_price' => (int) ($line['unit_price'] ?? $product?->selling_price ?? 0),
                'tax_rate' => $billingType->isGst()
                    ? $this->resolveTaxRate($line['tax_rate'] ?? null, $product)
                    : 0.0,
            ];

            $resolvedLines[] = ['line' => $line, 'product' => $product];
        }

        $totals = LineTotals::fromLines($preparedLines);

        foreach ($resolvedLines as $index => $resolved) {
            $line = $resolved['line'];
            $product = $resolved['product'];
            $computed = $totals['lines'][$index];

            SalesInvoiceLine::query()->create([
                'business_id' => $invoice->business_id,
                'sales_invoice_id' => $invoice->id,
                'line_number' => $index + 1,
                'product_id' => $product?->id,
                'unit_id' => $this->resolveUnitId($line['unit_id'] ?? null, $product),
                'description' => $line['description'] ?? $product?->name ?? 'Item',
                'quantity' => $computed['quantity'],
                'unit_price' => $computed['unit_price'],
                'tax_rate' => $computed['tax_rate'],
                'tax_amount' => $computed['tax'],
                'line_total' => $computed['total'],
                'hsn_sac' => $line['hsn_sac'] ?? $product?->hsn_sac,
            ]);
        }

        $invoice->update([
            'subtotal_amount' => $totals['subtotal'],
            'tax_amount' => $totals['tax'],
            'total_amount' => $totals['total'],
        ]);
    }

    /**
     * Load every product referenced by the lines in a single scoped query.
     *
     * The BelongsToBusiness scope means a product belonging to another business
     * is simply absent, and the line falls back to its own submitted values.
     *
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, Product>
     */
    private function productsForLines(array $lines): array
    {
        $productIds = collect($lines)
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return [];
        }

        return Product::query()
            ->whereIn('id', $productIds)
            ->with('unit')
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * Resolve the unit of measure for a single line.
     *
     * The unit stored against the selected product is the source of truth and is
     * read from the database through the product's own unit, so the line captures
     * the unit the product was sold by rather than whatever the request claimed.
     * A unit submitted by the client is only honoured for a manual line with no
     * product, and is re-checked here so a tampered request can never store a unit
     * belonging to another business.
     */
    private function resolveUnitId(mixed $submitted, ?Product $product): ?string
    {
        $productUnitId = $product?->unit?->id;

        if ($productUnitId !== null) {
            return $productUnitId;
        }

        if (! is_string($submitted) || $submitted === '') {
            return null;
        }

        return Unit::query()->whereKey($submitted)->exists() ? $submitted : null;
    }

    /**
     * Resolve the GST rate for a single line.
     *
     * The rate persisted against the selected product is the source of truth and
     * is read from the database rather than from the request. A rate supplied by
     * the client is honoured as a deliberate override, but it is re-validated
     * here so a tampered request can never store a negative, oversized or
     * over-precise GST rate.
     *
     * @throws InvalidGstRateException
     */
    private function resolveTaxRate(mixed $submitted, ?Product $product): float
    {
        if ($submitted === null || $submitted === '') {
            return GstRate::normalize($product?->tax_rate ?? 0);
        }

        return GstRate::normalize($submitted);
    }

    /**
     * Resolve the billing type and the print template a document will carry.
     *
     * The business is only supplied when a document is being created, because
     * that is the point where the business default is pinned onto the row. An
     * update passes no business so an existing invoice keeps whatever template
     * it was created with instead of silently adopting the current default.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{billing_type: BillingType, document_template: ?string}
     */
    private function resolveBillingAttributes(array $attributes, ?Business $business = null): array
    {
        $billingType = $attributes['billing_type'] ?? BillingType::Gst;

        if (! $billingType instanceof BillingType) {
            $billingType = BillingType::from($billingType);
        }

        $documentTemplate = $attributes['document_template'] ?? null;

        if ($documentTemplate === '') {
            $documentTemplate = null;
        }

        if ($documentTemplate === null && $business !== null) {
            $documentTemplate = $this->invoiceTemplateService->resolveForNewDocument($business, $billingType);
        }

        return [
            'billing_type' => $billingType,
            'document_template' => $documentTemplate,
        ];
    }
}
