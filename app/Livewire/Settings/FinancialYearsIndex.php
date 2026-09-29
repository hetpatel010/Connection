<?php

namespace App\Livewire\Settings;

use App\Application\Services\BusinessContext;
use App\Application\Services\FinancialYearService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\FinancialYear;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FinancialYearsIndex extends Component
{
    use DispatchesToasts;

    public function mount(): void
    {
        $this->authorize('viewAny', FinancialYear::class);
    }

    public function closeFinancialYear(string $financialYearId, FinancialYearService $financialYearService): void
    {
        $financialYear = FinancialYear::query()->withoutGlobalScopes()->findOrFail($financialYearId);
        $this->authorize('close', $financialYear);

        $financialYearService->close($financialYear, auth()->user());

        $this->toastSuccess(__('Financial year :label closed.', ['label' => $financialYear->label]));
    }

    public function openNextFinancialYear(FinancialYearService $financialYearService, BusinessContext $businessContext): void
    {
        $this->authorize('openNext', FinancialYear::class);

        $business = business_context()->business();
        abort_if($business === null, 404);

        $financialYear = $financialYearService->openNext($business, auth()->user());
        $businessContext->setFinancialYear($financialYear, auth()->user());

        $this->toastSuccess(__('Financial year :label opened and set as current.', ['label' => $financialYear->label]));

        $this->dispatch('financial-year-changed');
    }

    public function render(): View
    {
        $business = business_context()->business();

        $financialYears = $business
            ? FinancialYear::query()
                ->withoutGlobalScopes()
                ->with('closedByUser')
                ->where('business_id', $business->id)
                ->orderByDesc('starts_on')
                ->get()
            : collect();

        $latest = $financialYears->first();
        $canOpenNext = $latest !== null
            && $latest->status->value === 'closed'
            && ! $financialYears->contains(fn (FinancialYear $year): bool => $year->starts_on->gt($latest->ends_on));

        return view('livewire.settings.financial-years-index', [
            'financialYears' => $financialYears,
            'canOpenNext' => $canOpenNext,
        ]);
    }
}
