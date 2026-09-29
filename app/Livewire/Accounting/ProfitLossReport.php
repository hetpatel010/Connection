<?php

namespace App\Livewire\Accounting;

use App\Application\Services\ProfitAndLossService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class ProfitLossReport extends Component
{
    public string $fromDate = '';

    public string $toDate = '';

    public function mount(): void
    {
        $financialYear = business_context()->financialYear();

        if ($financialYear !== null) {
            $this->fromDate = $financialYear->starts_on->toDateString();
            $this->toDate = now()->toDateString();
        }
    }

    public function render(ProfitAndLossService $profitAndLossService): View
    {
        $report = [
            'income' => collect(),
            'expenses' => collect(),
            'total_income' => 0,
            'total_expenses' => 0,
            'net_profit' => 0,
            'from' => $this->fromDate,
            'to' => $this->toDate,
        ];

        if (
            business_context()->business() !== null
            && business_context()->financialYear() !== null
            && $this->fromDate !== ''
            && $this->toDate !== ''
        ) {
            $report = $profitAndLossService->generate(
                business_context()->business(),
                business_context()->financialYear(),
                Carbon::parse($this->fromDate),
                Carbon::parse($this->toDate),
            );
        }

        return view('livewire.accounting.profit-loss-report', [
            'report' => $report,
        ]);
    }
}
