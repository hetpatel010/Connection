<?php

namespace App\Application\Services;

use App\Domain\Shared\Exceptions\InvalidDocumentStateException;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Models\DebitNote;
use App\Models\DebitNoteLine;
use App\Models\PurchaseBill;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DebitNoteService
{
    public function __construct(
        private AccountingService $accountingService,
        private DocumentNumberService $documentNumberService,
        private TransactionJournalBuilder $journalBuilder,
        private InventoryService $inventoryService,
    ) {}

    public function createFromBill(PurchaseBill $bill, User $user, ?string $reason = null): DebitNote
    {
        if ($bill->status !== DocumentStatus::Confirmed) {
            throw InvalidDocumentStateException::mustBeConfirmed('debited');
        }

        $bill->load('lines', 'paymentAccount');

        return DB::transaction(function () use ($bill, $user, $reason): DebitNote {
            $debitNote = DebitNote::query()->create([
                'business_id' => $bill->business_id,
                'financial_year_id' => $bill->financial_year_id,
                'debit_note_number' => $this->documentNumberService->nextNumber(
                    $bill->business,
                    'debit_note',
                    'DN',
                    $bill->financialYear,
                ),
                'source_purchase_bill_id' => $bill->id,
                'party_id' => $bill->party_id,
                'debit_note_date' => now()->toDateString(),
                'settlement_mode' => $bill->settlement_mode,
                'payment_account_id' => $bill->payment_account_id,
                'status' => DocumentStatus::Draft,
                'subtotal_amount' => $bill->subtotal_amount,
                'tax_amount' => $bill->tax_amount,
                'total_amount' => $bill->total_amount,
                'notes' => $reason,
                'reference' => $bill->bill_number,
                'created_by' => $user->id,
            ]);

            foreach ($bill->lines as $line) {
                DebitNoteLine::query()->create([
                    'business_id' => $debitNote->business_id,
                    'debit_note_id' => $debitNote->id,
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

            $debitNote->load('lines', 'paymentAccount');

            $journal = $this->accountingService->postJournal(
                business: $debitNote->business,
                financialYear: $debitNote->financialYear,
                user: $user,
                transactionDate: $debitNote->debit_note_date,
                description: 'Debit note '.$debitNote->debit_note_number,
                lines: $this->journalBuilder->debitNote(
                    $debitNote->business,
                    $debitNote->subtotal_amount,
                    $debitNote->tax_amount,
                    $debitNote->total_amount,
                    $debitNote->settlement_mode,
                    $debitNote->paymentAccount,
                ),
                sourceType: 'debit_note',
                sourceId: $debitNote->id,
            );

            $debitNote->update([
                'status' => DocumentStatus::Confirmed,
                'journal_id' => $journal->id,
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);

            $this->inventoryService->recordPurchaseReturn($debitNote, $user);

            return $debitNote->fresh(['lines', 'party', 'journal', 'sourcePurchaseBill']);
        });
    }
}
