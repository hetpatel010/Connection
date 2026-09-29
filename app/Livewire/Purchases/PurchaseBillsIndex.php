<?php

namespace App\Livewire\Purchases;

use App\Application\Services\DebitNoteService;
use App\Application\Services\PaymentAllocationService;
use App\Application\Services\PurchaseBillService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\DebitNote;
use App\Models\PurchaseBill;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseBillsIndex extends Component
{
    use DispatchesToasts;
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDraft(string $billId, PurchaseBillService $purchaseBillService): void
    {
        $bill = PurchaseBill::query()->findOrFail($billId);
        $this->authorize('confirm', $bill);

        $purchaseBillService->confirm($bill, auth()->user());

        $this->toastSuccess(__('Bill :number confirmed.', ['number' => $bill->bill_number]));
    }

    public function deleteDraft(string $billId, PurchaseBillService $purchaseBillService): void
    {
        $bill = PurchaseBill::query()->findOrFail($billId);
        $this->authorize('delete', $bill);

        $number = $bill->bill_number;
        $purchaseBillService->deleteDraft($bill, auth()->user());

        $this->toastSuccess(__('Draft bill :number deleted.', ['number' => $number]));
    }

    public function reverseBill(string $billId, PurchaseBillService $purchaseBillService): void
    {
        $bill = PurchaseBill::query()->findOrFail($billId);
        $this->authorize('reverse', $bill);

        $purchaseBillService->reverse($bill, auth()->user());

        $this->toastSuccess(__('Bill :number reversed.', ['number' => $bill->bill_number]));
    }

    public function cancelBill(string $billId, PurchaseBillService $purchaseBillService): void
    {
        $bill = PurchaseBill::query()->findOrFail($billId);
        $this->authorize('cancel', $bill);

        $purchaseBillService->cancel($bill, auth()->user());

        $this->toastSuccess(__('Bill :number cancelled.', ['number' => $bill->bill_number]));
    }

    public function issueDebitNote(string $billId, DebitNoteService $debitNoteService): void
    {
        $bill = PurchaseBill::query()->findOrFail($billId);
        $this->authorize('create', DebitNote::class);

        $debitNote = $debitNoteService->createFromBill($bill, auth()->user());

        $this->toastSuccess(__('Debit note :number issued for bill :bill.', [
            'number' => $debitNote->debit_note_number,
            'bill' => $bill->bill_number,
        ]));
    }

    public function render(PaymentAllocationService $allocationService): View
    {
        $this->authorize('viewAny', PurchaseBill::class);

        $bills = PurchaseBill::query()
            ->with('party')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('bill_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderByDesc('bill_date')
            ->paginate(15);

        $allocatedAmounts = $allocationService->allocatedAmountsFor(
            'purchase_bill',
            $bills->getCollection()->pluck('id')->all(),
        );

        return view('livewire.purchases.purchase-bills-index', [
            'bills' => $bills,
            'allocatedAmounts' => $allocatedAmounts,
        ]);
    }
}
