<?php

namespace App\Application\Services;

use App\Domain\Accounting\Enums\BalanceSide;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\PaymentType;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\Business;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PartyLedgerService
{
    public function __construct(
        private PaymentAllocationService $allocationService,
    ) {}

    public function signedOpeningBalance(Party $party, bool $receivable): int
    {
        $amount = $party->opening_balance ?? 0;

        if ($amount === 0) {
            return 0;
        }

        $side = $party->opening_balance_side ?? BalanceSide::Debit;

        if ($receivable) {
            return $side === BalanceSide::Debit ? $amount : -$amount;
        }

        return $side === BalanceSide::Credit ? $amount : -$amount;
    }

    public function partyBalance(Business $business, Party $party, bool $receivable): int
    {
        $balance = $this->signedOpeningBalance($party, $receivable);

        if ($receivable) {
            $balance += $this->netDocumentTotal($business, $party, SalesInvoice::class, 'invoice_date');
            $balance -= $this->netPaymentTotal($business, $party, PaymentType::PaymentIn);

            return $balance;
        }

        $balance += $this->netDocumentTotal($business, $party, PurchaseBill::class, 'bill_date');
        $balance -= $this->netPaymentTotal($business, $party, PaymentType::PaymentOut);

        return $balance;
    }

    /**
     * @return array{
     *     as_of: string,
     *     rows: Collection<int, array{
     *         party_id: string,
     *         party_name: string,
     *         total_outstanding: int,
     *         current: int,
     *         days_31_60: int,
     *         days_61_90: int,
     *         days_90_plus: int
     *     }>,
     *     totals: array{
     *         total_outstanding: int,
     *         current: int,
     *         days_31_60: int,
     *         days_61_90: int,
     *         days_90_plus: int
     *     }
     * }
     */
    public function ageingReport(Business $business, bool $receivable, Carbon $asOf): array
    {
        $parties = Party::query()
            ->where('business_id', $business->id)
            ->when($receivable, fn ($query) => $query->customers())
            ->when(! $receivable, fn ($query) => $query->suppliers())
            ->orderBy('name')
            ->get();

        $rows = $parties->map(function (Party $party) use ($business, $receivable, $asOf): array {
            $buckets = [
                'current' => 0,
                'days_31_60' => 0,
                'days_61_90' => 0,
                'days_90_plus' => 0,
            ];

            $opening = $this->signedOpeningBalance($party, $receivable);

            if ($opening !== 0) {
                $buckets['current'] += $opening;
            }

            $documents = $receivable
                ? $this->openCreditDocuments($business, $party, SalesInvoice::class)
                : $this->openCreditDocuments($business, $party, PurchaseBill::class);

            foreach ($documents as $document) {
                $outstanding = $this->allocationService->outstandingBalance($document);

                if ($outstanding <= 0) {
                    continue;
                }

                $referenceDate = $this->referenceDateFor($document);
                $ageDays = $referenceDate->diffInDays($asOf, false);
                $bucket = $this->ageingBucket(max(0, $ageDays));
                $buckets[$bucket] += $outstanding;
            }

            $totalOutstanding = array_sum($buckets);

            return [
                'party_id' => $party->id,
                'party_name' => $party->name,
                'total_outstanding' => $totalOutstanding,
                'current' => $buckets['current'],
                'days_31_60' => $buckets['days_31_60'],
                'days_61_90' => $buckets['days_61_90'],
                'days_90_plus' => $buckets['days_90_plus'],
            ];
        })->filter(fn (array $row): bool => $row['total_outstanding'] > 0)->values();

        return [
            'as_of' => $asOf->toDateString(),
            'rows' => $rows,
            'totals' => [
                'total_outstanding' => (int) $rows->sum('total_outstanding'),
                'current' => (int) $rows->sum('current'),
                'days_31_60' => (int) $rows->sum('days_31_60'),
                'days_61_90' => (int) $rows->sum('days_61_90'),
                'days_90_plus' => (int) $rows->sum('days_90_plus'),
            ],
        ];
    }

    /**
     * @return array{
     *     party: Party,
     *     receivable: bool,
     *     from: string,
     *     to: string,
     *     opening_balance: int,
     *     closing_balance: int,
     *     lines: Collection<int, array{
     *         date: string,
     *         type: string,
     *         reference: string,
     *         debit: int,
     *         credit: int,
     *         balance: int
     *     }>
     * }
     */
    public function statement(Business $business, Party $party, Carbon $from, Carbon $to, bool $receivable): array
    {
        $entries = $this->collectEntries($business, $party, $receivable);

        $openingBalance = $this->signedOpeningBalance($party, $receivable);

        foreach ($entries->filter(fn (array $entry): bool => Carbon::parse($entry['date'])->lt($from)) as $entry) {
            $openingBalance += $entry['debit'] - $entry['credit'];
        }

        $runningBalance = $openingBalance;
        $lines = collect([
            [
                'date' => $from->toDateString(),
                'type' => 'opening',
                'reference' => __('Opening balance'),
                'debit' => $openingBalance > 0 ? $openingBalance : 0,
                'credit' => $openingBalance < 0 ? abs($openingBalance) : 0,
                'balance' => $openingBalance,
            ],
        ]);

        $periodEntries = $entries
            ->filter(fn (array $entry): bool => Carbon::parse($entry['date'])->betweenIncluded($from, $to))
            ->sortBy([
                fn (array $entry): string => $entry['date'],
                fn (array $entry): string => $entry['reference'],
            ])
            ->values();

        foreach ($periodEntries as $entry) {
            $runningBalance += $entry['debit'] - $entry['credit'];

            $lines->push([
                'date' => $entry['date'],
                'type' => $entry['type'],
                'reference' => $entry['reference'],
                'debit' => $entry['debit'],
                'credit' => $entry['credit'],
                'balance' => $runningBalance,
            ]);
        }

        return [
            'party' => $party,
            'receivable' => $receivable,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'lines' => $lines,
        ];
    }

    /**
     * @return Collection<int, array{date: string, type: string, reference: string, debit: int, credit: int}>
     */
    private function collectEntries(Business $business, Party $party, bool $receivable): Collection
    {
        $entries = collect();

        if ($receivable) {
            SalesInvoice::query()
                ->where('business_id', $business->id)
                ->where('party_id', $party->id)
                ->where('settlement_mode', SettlementMode::Credit)
                ->whereIn('status', [DocumentStatus::Confirmed, DocumentStatus::Reversed])
                ->orderBy('invoice_date')
                ->each(function (SalesInvoice $invoice) use ($entries): void {
                    if ($invoice->status === DocumentStatus::Confirmed) {
                        $entries->push([
                            'date' => $invoice->invoice_date->toDateString(),
                            'type' => 'invoice',
                            'reference' => $invoice->invoice_number,
                            'debit' => $invoice->total_amount,
                            'credit' => 0,
                        ]);
                    }

                    if ($invoice->status === DocumentStatus::Reversed && $invoice->confirmed_at !== null) {
                        $entries->push([
                            'date' => $invoice->updated_at->toDateString(),
                            'type' => 'reversal',
                            'reference' => $invoice->invoice_number.' (reversed)',
                            'debit' => 0,
                            'credit' => $invoice->total_amount,
                        ]);
                    }
                });

            Payment::query()
                ->where('business_id', $business->id)
                ->where('party_id', $party->id)
                ->where('payment_type', PaymentType::PaymentIn)
                ->whereIn('status', [DocumentStatus::Confirmed, DocumentStatus::Reversed])
                ->orderBy('payment_date')
                ->each(function (Payment $payment) use ($entries): void {
                    if ($payment->status === DocumentStatus::Confirmed) {
                        $entries->push([
                            'date' => $payment->payment_date->toDateString(),
                            'type' => 'payment',
                            'reference' => $payment->payment_number,
                            'debit' => 0,
                            'credit' => $payment->amount,
                        ]);
                    }

                    if ($payment->status === DocumentStatus::Reversed) {
                        $entries->push([
                            'date' => $payment->updated_at->toDateString(),
                            'type' => 'reversal',
                            'reference' => $payment->payment_number.' (reversed)',
                            'debit' => $payment->amount,
                            'credit' => 0,
                        ]);
                    }
                });

            return $entries;
        }

        PurchaseBill::query()
            ->where('business_id', $business->id)
            ->where('party_id', $party->id)
            ->where('settlement_mode', SettlementMode::Credit)
            ->whereIn('status', [DocumentStatus::Confirmed, DocumentStatus::Reversed])
            ->orderBy('bill_date')
            ->each(function (PurchaseBill $bill) use ($entries): void {
                if ($bill->status === DocumentStatus::Confirmed) {
                    $entries->push([
                        'date' => $bill->bill_date->toDateString(),
                        'type' => 'bill',
                        'reference' => $bill->bill_number,
                        'debit' => $bill->total_amount,
                        'credit' => 0,
                    ]);
                }

                if ($bill->status === DocumentStatus::Reversed && $bill->confirmed_at !== null) {
                    $entries->push([
                        'date' => $bill->updated_at->toDateString(),
                        'type' => 'reversal',
                        'reference' => $bill->bill_number.' (reversed)',
                        'debit' => 0,
                        'credit' => $bill->total_amount,
                    ]);
                }
            });

        Payment::query()
            ->where('business_id', $business->id)
            ->where('party_id', $party->id)
            ->where('payment_type', PaymentType::PaymentOut)
            ->whereIn('status', [DocumentStatus::Confirmed, DocumentStatus::Reversed])
            ->orderBy('payment_date')
            ->each(function (Payment $payment) use ($entries): void {
                if ($payment->status === DocumentStatus::Confirmed) {
                    $entries->push([
                        'date' => $payment->payment_date->toDateString(),
                        'type' => 'payment',
                        'reference' => $payment->payment_number,
                        'debit' => 0,
                        'credit' => $payment->amount,
                    ]);
                }

                if ($payment->status === DocumentStatus::Reversed) {
                    $entries->push([
                        'date' => $payment->updated_at->toDateString(),
                        'type' => 'reversal',
                        'reference' => $payment->payment_number.' (reversed)',
                        'debit' => $payment->amount,
                        'credit' => 0,
                    ]);
                }
            });

        return $entries;
    }

    /**
     * @param  class-string<SalesInvoice|PurchaseBill>  $modelClass
     */
    private function netDocumentTotal(Business $business, Party $party, string $modelClass, string $dateColumn): int
    {
        $confirmed = (int) $modelClass::query()
            ->where('business_id', $business->id)
            ->where('party_id', $party->id)
            ->where('settlement_mode', SettlementMode::Credit)
            ->where('status', DocumentStatus::Confirmed)
            ->sum('total_amount');

        $reversed = (int) $modelClass::query()
            ->where('business_id', $business->id)
            ->where('party_id', $party->id)
            ->where('settlement_mode', SettlementMode::Credit)
            ->where('status', DocumentStatus::Reversed)
            ->sum('total_amount');

        return $confirmed - $reversed;
    }

    private function netPaymentTotal(Business $business, Party $party, PaymentType $paymentType): int
    {
        $confirmed = (int) Payment::query()
            ->where('business_id', $business->id)
            ->where('party_id', $party->id)
            ->where('payment_type', $paymentType)
            ->where('status', DocumentStatus::Confirmed)
            ->sum('amount');

        $reversed = (int) Payment::query()
            ->where('business_id', $business->id)
            ->where('party_id', $party->id)
            ->where('payment_type', $paymentType)
            ->where('status', DocumentStatus::Reversed)
            ->sum('amount');

        return $confirmed - $reversed;
    }

    /**
     * @param  class-string<SalesInvoice|PurchaseBill>  $modelClass
     * @return Collection<int, SalesInvoice|PurchaseBill>
     */
    private function openCreditDocuments(Business $business, Party $party, string $modelClass): Collection
    {
        $dateColumn = $modelClass === SalesInvoice::class ? 'invoice_date' : 'bill_date';

        return $modelClass::query()
            ->where('business_id', $business->id)
            ->where('party_id', $party->id)
            ->where('status', DocumentStatus::Confirmed)
            ->where('settlement_mode', SettlementMode::Credit)
            ->orderBy($dateColumn)
            ->get()
            ->filter(fn (SalesInvoice|PurchaseBill $document): bool => $this->allocationService->outstandingBalance($document) > 0)
            ->values();
    }

    private function referenceDateFor(SalesInvoice|PurchaseBill $document): Carbon
    {
        $dueDate = $document->due_date;

        if ($dueDate !== null) {
            return Carbon::parse($dueDate);
        }

        return Carbon::parse($document instanceof SalesInvoice ? $document->invoice_date : $document->bill_date);
    }

    private function ageingBucket(int $ageDays): string
    {
        return match (true) {
            $ageDays <= 30 => 'current',
            $ageDays <= 60 => 'days_31_60',
            $ageDays <= 90 => 'days_61_90',
            default => 'days_90_plus',
        };
    }
}
