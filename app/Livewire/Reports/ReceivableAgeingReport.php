<?php

namespace App\Livewire\Reports;

use App\Application\Services\PartyLedgerService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class ReceivableAgeingReport extends Component
{
    public string $asOfDate = '';

    public function mount(): void
    {
        $this->asOfDate = now()->toDateString();
    }

    public function render(PartyLedgerService $partyLedgerService): View
    {
        $report = [
            'as_of' => $this->asOfDate,
            'rows' => collect(),
            'totals' => [
                'total_outstanding' => 0,
                'current' => 0,
                'days_31_60' => 0,
                'days_61_90' => 0,
                'days_90_plus' => 0,
            ],
        ];

        if (business_context()->business() !== null && $this->asOfDate !== '') {
            $report = $partyLedgerService->ageingReport(
                business_context()->business(),
                receivable: true,
                asOf: Carbon::parse($this->asOfDate),
            );
        }

        return view('livewire.reports.receivable-ageing-report', [
            'report' => $report,
        ]);
    }
}
