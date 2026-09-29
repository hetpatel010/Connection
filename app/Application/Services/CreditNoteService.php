<?php

namespace App\Application\Services;

use App\Domain\Shared\Exceptions\InvalidDocumentStateException;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Models\CreditNote;
use App\Models\CreditNoteLine;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    public function __construct(
        private AccountingService $accountingService,
        private DocumentNumberService $documentNumberService,
        private TransactionJournalBuilder $journalBuilder,
        private InventoryService $inventoryService,
    ) {}

    public function createFromInvoice(SalesInvoice $invoice, User $user, ?string $reason = null): CreditNote
    {
        if ($invoice->status !== DocumentStatus::Confirmed) {
            throw InvalidDocumentStateException::mustBeConfirmed('credited');
        }

        $invoice->load('lines', 'paymentAccount');

        return DB::transaction(function () use ($invoice, $user, $reason): CreditNote {
            $creditNote = CreditNote::query()->create([
                'business_id' => $invoice->business_id,
                'financial_year_id' => $invoice->financial_year_id,
                'credit_note_number' => $this->documentNumberService->nextNumber(
                    $invoice->business,
                    'credit_note',
                    'CN',
                    $invoice->financialYear,
                ),
                'source_sales_invoice_id' => $invoice->id,
                'party_id' => $invoice->party_id,
                'credit_note_date' => now()->toDateString(),
                'settlement_mode' => $invoice->settlement_mode,
                'payment_account_id' => $invoice->payment_account_id,
                'status' => DocumentStatus::Draft,
                'subtotal_amount' => $invoice->subtotal_amount,
                'tax_amount' => $invoice->tax_amount,
                'total_amount' => $invoice->total_amount,
                'notes' => $reason,
                'reference' => $invoice->invoice_number,
                'created_by' => $user->id,
            ]);

            foreach ($invoice->lines as $line) {
                CreditNoteLine::query()->create([
                    'business_id' => $creditNote->business_id,
                    'credit_note_id' => $creditNote->id,
                    'line_number' => $line->line_number,
                    'product_id' => $line->product_id,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'tax_rate' => $line->tax_rate,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                    'hsn_sac' => $line->hsn_sac,
                ]);
            }

            $creditNote->load('lines', 'paymentAccount');

            $journal = $this->accountingService->postJournal(
                business: $creditNote->business,
                financialYear: $creditNote->financialYear,
                user: $user,
                transactionDate: $creditNote->credit_note_date,
                description: 'Credit note '.$creditNote->credit_note_number,
                lines: $this->journalBuilder->creditNote(
                    $creditNote->business,
                    $creditNote->subtotal_amount,
                    $creditNote->tax_amount,
                    $creditNote->total_amount,
                    $creditNote->settlement_mode,
                    $creditNote->paymentAccount,
                ),
                sourceType: 'credit_note',
                sourceId: $creditNote->id,
            );

            $creditNote->update([
                'status' => DocumentStatus::Confirmed,
                'journal_id' => $journal->id,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            $this->inventoryService->recordSaleReturn($creditNote, $user);

            return $creditNote->fresh(['lines', 'party', 'journal', 'sourceSalesInvoice']);
        });
    }
}
