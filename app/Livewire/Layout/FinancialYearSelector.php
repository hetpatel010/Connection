<?php

namespace App\Livewire\Layout;

use App\Application\Services\BusinessContext;
use App\Models\FinancialYear;
use Illuminate\Support\Collection;
use Livewire\Component;

class FinancialYearSelector extends Component
{
    public ?string $selectedFinancialYearId = null;

    /** @var Collection<int, FinancialYear> */
    public Collection $financialYears;

    public function mount(BusinessContext $businessContext): void
    {
        $business = $businessContext->business();

        $this->financialYears = $business
            ? FinancialYear::query()
                ->withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->orderByDesc('starts_on')
                ->get()
            : collect();

        $this->selectedFinancialYearId = $businessContext->financialYearId();
    }

    public function updatedSelectedFinancialYearId(?string $financialYearId, BusinessContext $businessContext): void
    {
        if ($financialYearId === null || $businessContext->business() === null) {
            return;
        }

        $financialYear = $this->financialYears->firstWhere('id', $financialYearId);

        if ($financialYear === null) {
            return;
        }

        $businessContext->setFinancialYear($financialYear, auth()->user());
        $this->dispatch('financial-year-changed');
    }

    public function render()
    {
        return view('livewire.layout.financial-year-selector');
    }
}
