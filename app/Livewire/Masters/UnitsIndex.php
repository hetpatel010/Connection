<?php

namespace App\Livewire\Masters;

use App\Application\Services\UnitService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UnitsIndex extends Component
{
    use DispatchesToasts;

    public string $name = '';

    public string $symbol = '';

    public int $decimalPlaces = 0;

    public bool $isDefault = false;

    public function saveUnit(UnitService $unitService): void
    {
        $this->authorize('manage_units');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:20'],
            'decimalPlaces' => ['required', 'integer', 'min:0', 'max:4'],
            'isDefault' => ['boolean'],
        ]);

        $unitService->create(business_context()->business(), [
            'name' => $validated['name'],
            'symbol' => $validated['symbol'],
            'decimal_places' => $validated['decimalPlaces'],
            'is_default' => $validated['isDefault'],
        ]);

        $this->reset(['name', 'symbol', 'decimalPlaces', 'isDefault']);
        $this->toastSuccess(__('Unit created successfully.'));
    }

    public function render(): View
    {
        $this->authorize('manage_units');

        return view('livewire.masters.units-index', [
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }
}
