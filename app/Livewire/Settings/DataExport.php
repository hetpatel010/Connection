<?php

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class DataExport extends Component
{
    public function mount(): void
    {
        $business = business_context()->business();
        abort_if($business === null, 404);

        $this->authorize('exportData', $business);
    }

    public function render(): View
    {
        $business = business_context()->business();
        $financialYear = business_context()->financialYear();

        return view('livewire.settings.data-export', [
            'business' => $business,
            'financialYear' => $financialYear,
        ]);
    }
}
