<?php

namespace App\Livewire\Sales;

use App\Models\CreditNote;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CreditNotesIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', CreditNote::class);

        $creditNotes = CreditNote::query()
            ->with(['party', 'sourceSalesInvoice'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('credit_note_number', 'like', '%'.$this->search.'%')
                        ->orWhere('reference', 'like', '%'.$this->search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderByDesc('credit_note_date')
            ->paginate(15);

        return view('livewire.sales.credit-notes-index', [
            'creditNotes' => $creditNotes,
        ]);
    }
}
