<?php

namespace App\Livewire\Accounting;

use App\Application\Services\TrialBalanceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TrialBalanceReport extends Component
{
    public function render(TrialBalanceService $trialBalanceService): View
    {
        $report = [
            'rows' => collect(),
            'total_debits' => 0,
            'total_credits' => 0,
        ];

        if (business_context()->business() !== null && business_context()->financialYear() !== null) {
            $report = $trialBalanceService->generate(
                business_context()->business(),
                business_context()->financialYear(),
            );
        }

        return view('livewire.accounting.trial-balance-report', [
            'report' => $report,
        ]);
    }
}
