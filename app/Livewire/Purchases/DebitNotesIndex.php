<?php

namespace App\Livewire\Purchases;

use App\Models\DebitNote;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class DebitNotesIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', DebitNote::class);

        $debitNotes = DebitNote::query()
            ->with(['party', 'sourcePurchaseBill'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('debit_note_number', 'like', '%'.$this->search.'%')
                        ->orWhere('reference', 'like', '%'.$this->search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderByDesc('debit_note_date')
            ->paginate(15);

        return view('livewire.purchases.debit-notes-index', [
            'debitNotes' => $debitNotes,
        ]);
    }
}
