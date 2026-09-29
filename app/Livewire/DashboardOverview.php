<?php

namespace App\Livewire;

use App\Application\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class DashboardOverview extends Component
{
    public function render(DashboardService $dashboardService): View
    {
        $metrics = [
            'todays_sales' => 0,
            'monthly_sales' => 0,
            'gst_collected_month' => 0,
            'low_stock_count' => 0,
            'receivables' => 0,
            'payables' => 0,
            'cash_and_bank' => 0,
            'recent_invoices' => collect(),
            'setup' => [
                'has_gstin' => false,
                'has_customers' => false,
                'has_products' => false,
                'has_invoices' => false,
                'is_complete' => false,
            ],
        ];

        if (business_context()->business() !== null && business_context()->financialYear() !== null) {
            $metrics = $dashboardService->metrics(
                business_context()->business(),
                business_context()->financialYear(),
            );
        }

        return view('livewire.dashboard-overview', [
            'metrics' => $metrics,
        ]);
    }
}
