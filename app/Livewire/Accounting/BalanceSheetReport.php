<?php

namespace App\Livewire\Accounting;

use App\Application\Services\BalanceSheetService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class BalanceSheetReport extends Component
{
    public string $asOfDate = '';

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    public function render(BalanceSheetService $balanceSheetService): View
    {
        $report = [
            'assets' => collect(),
            'liabilities' => collect(),
            'equity' => collect(),
            'current_period_profit' => 0,
            'total_assets' => 0,
            'total_liabilities_and_equity' => 0,
            'as_of' => $this->asOfDate,
        ];

        if (
            business_context()->business() !== null
            && business_context()->financialYear() !== null
            && $this->asOfDate !== ''
        ) {
            $report = $balanceSheetService->generate(
                business_context()->business(),
                business_context()->financialYear(),
                Carbon::parse($this->asOfDate),
            );
        }

        return view('livewire.accounting.balance-sheet-report', [
            'report' => $report,
        ]);
    }
}
