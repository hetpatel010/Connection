<?php

namespace App\Livewire\Layout;

use App\Application\Services\BusinessContext;
use App\Models\Business;
use Illuminate\Support\Collection;
use Livewire\Component;

class BusinessSwitcher extends Component
{
    public ?string $selectedBusinessId = null;

    /** @var Collection<int, Business> */
    public Collection $businesses;

    public function mount(BusinessContext $businessContext): void
    {
        $this->businesses = auth()->user()->businesses()->orderBy('name')->get();
        $this->selectedBusinessId = $businessContext->businessId();
    }

    public function updatedSelectedBusinessId(?string $businessId, BusinessContext $businessContext): void
    {
        if ($businessId === null) {
            return;
        }

        $business = $this->businesses->firstWhere('id', $businessId);

        if ($business === null) {
            return;
        }

        $businessContext->setBusiness($business, auth()->user());
        $this->dispatch('business-changed');
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.layout.business-switcher');
    }
}
