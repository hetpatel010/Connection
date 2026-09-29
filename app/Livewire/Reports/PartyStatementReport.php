<?php

namespace App\Livewire\Reports;

use App\Application\Services\PartyLedgerService;
use App\Models\Party;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Component;

class PartyStatementReport extends Component
{
    public string $partyId = '';

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

    public function render(PartyLedgerService $partyLedgerService): View
    {
        $parties = Party::query()->orderBy('name')->get();
        $statement = null;

        if (
            business_context()->business() !== null
            && $this->partyId !== ''
            && $this->fromDate !== ''
            && $this->toDate !== ''
        ) {
            $party = Party::query()->findOrFail($this->partyId);
            $receivable = $party->party_type->isCustomer();

            if ($receivable) {
                $this->authorize('viewAny', SalesInvoice::class);
            } else {
                $this->authorize('viewAny', PurchaseBill::class);
            }

            $statement = $partyLedgerService->statement(
                business: business_context()->business(),
                party: $party,
                from: Carbon::parse($this->fromDate),
                to: Carbon::parse($this->toDate),
                receivable: $receivable,
            );
        }

        return view('livewire.reports.party-statement-report', [
            'parties' => $parties,
            'statement' => $statement,
        ]);
    }
}
