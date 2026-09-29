<?php

namespace App\Livewire\Accounting;

use App\Application\Services\GstReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class GstSummaryReport extends Component
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

    public function render(GstReportService $gstReportService): View
    {
        $report = [
            'output_tax' => 0,
            'input_tax' => 0,
            'net_payable' => 0,
            'output_by_rate' => collect(),
            'input_by_rate' => collect(),
            'from' => $this->fromDate,
            'to' => $this->toDate,
        ];

        if (
            business_context()->business() !== null
            && $this->fromDate !== ''
            && $this->toDate !== ''
        ) {
            $report = $gstReportService->generate(
                business_context()->business(),
                Carbon::parse($this->fromDate),
                Carbon::parse($this->toDate),
            );
        }

        return view('livewire.accounting.gst-summary-report', [
            'report' => $report,
        ]);
    }
}
