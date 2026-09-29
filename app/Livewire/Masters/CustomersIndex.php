<?php

namespace App\Livewire\Masters;

use App\Models\Party;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class CustomersIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $this->authorize('viewAny', Party::class);

        $customers = Party::query()
            ->customers()
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('gstin', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.masters.customers-index', [
            'customers' => $customers,
        ]);
    }
}
