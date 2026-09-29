<?php

namespace App\Livewire\Sales;

use App\Application\Services\CreditNoteService;
use App\Application\Services\PaymentAllocationService;
use App\Application\Services\SalesInvoiceService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\CreditNote;
use App\Models\SalesInvoice;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class InvoicesIndex extends Component
{
    use DispatchesToasts;
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDraft(string $invoiceId, SalesInvoiceService $salesInvoiceService): void
    {
        $invoice = SalesInvoice::query()->findOrFail($invoiceId);
        $this->authorize('confirm', $invoice);

        $salesInvoiceService->confirm($invoice, auth()->user());

        $this->toastSuccess(__('Invoice :number confirmed.', ['number' => $invoice->invoice_number]));
    }

    public function deleteDraft(string $invoiceId, SalesInvoiceService $salesInvoiceService): void
    {
        $invoice = SalesInvoice::query()->findOrFail($invoiceId);
        $this->authorize('delete', $invoice);

        $number = $invoice->invoice_number;
        $salesInvoiceService->deleteDraft($invoice, auth()->user());

        $this->toastSuccess(__('Draft invoice :number deleted.', ['number' => $number]));
    }

    public function reverseInvoice(string $invoiceId, SalesInvoiceService $salesInvoiceService): void
    {
        $invoice = SalesInvoice::query()->findOrFail($invoiceId);
        $this->authorize('reverse', $invoice);

        $salesInvoiceService->reverse($invoice, auth()->user());

        $this->toastSuccess(__('Invoice :number reversed.', ['number' => $invoice->invoice_number]));
    }

    public function cancelInvoice(string $invoiceId, SalesInvoiceService $salesInvoiceService): void
    {
        $invoice = SalesInvoice::query()->findOrFail($invoiceId);
        $this->authorize('cancel', $invoice);

        $salesInvoiceService->cancel($invoice, auth()->user());

        $this->toastSuccess(__('Invoice :number cancelled.', ['number' => $invoice->invoice_number]));
    }

    public function issueCreditNote(string $invoiceId, CreditNoteService $creditNoteService): void
    {
        $invoice = SalesInvoice::query()->findOrFail($invoiceId);
        $this->authorize('create', CreditNote::class);

        $creditNote = $creditNoteService->createFromInvoice($invoice, auth()->user());

        $this->toastSuccess(__('Credit note :number issued for invoice :invoice.', [
            'number' => $creditNote->credit_note_number,
            'invoice' => $invoice->invoice_number,
        ]));
    }

    public function render(PaymentAllocationService $allocationService): View
    {
        $this->authorize('viewAny', SalesInvoice::class);

        $invoices = SalesInvoice::query()
            ->with('party')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('invoice_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderByDesc('invoice_date')
            ->paginate(15);

        $allocatedAmounts = $allocationService->allocatedAmountsFor(
            'sales_invoice',
            $invoices->getCollection()->pluck('id')->all(),
        );

        return view('livewire.sales.invoices-index', [
            'invoices' => $invoices,
            'allocatedAmounts' => $allocatedAmounts,
        ]);
    }
}
