<?php

namespace App\Livewire\Accounting;

use App\Application\Services\LedgerService;
use App\Models\Account;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LedgerReport extends Component
{
    public ?string $accountId = null;

    public function mount(): void
    {
        $this->accountId = Account::query()
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->value('id');
    }

    public function render(LedgerService $ledgerService): View
    {
        $accounts = Account::query()
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        $selectedAccount = $accounts->firstWhere('id', $this->accountId);
        $entries = collect();

        if ($selectedAccount !== null && business_context()->financialYear() !== null) {
            $entries = $ledgerService->accountLedger(
                $selectedAccount,
                business_context()->financialYear(),
            );
        }

        return view('livewire.accounting.ledger-report', [
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'entries' => $entries,
        ]);
    }
}
