<?php

namespace App\Livewire\Accounting;

use App\Application\Services\ChartOfAccountsService;
use App\Application\Services\LedgerService;
use App\Domain\Accounting\Enums\AccountStatus;
use App\Domain\Accounting\Exceptions\AccountHasTransactionsException;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Account;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AccountShow extends Component
{
    use DispatchesToasts;

    public Account $account;

    public function mount(Account $account): void
    {
        $this->authorize('view', $account);

        $this->account = $account;
    }

    public function toggleStatus(ChartOfAccountsService $chartOfAccountsService): void
    {
        $this->authorize('update', $this->account);

        $next = $this->account->status === AccountStatus::Active
            ? AccountStatus::Inactive
            : AccountStatus::Active;

        $chartOfAccountsService->setStatus($this->account, $next);

        $this->toastSuccess($next === AccountStatus::Active
            ? __('Account activated successfully.')
            : __('Account deactivated successfully.'));
    }

    public function delete(ChartOfAccountsService $chartOfAccountsService): void
    {
        $this->authorize('delete', $this->account);

        $code = $this->account->code;

        try {
            $chartOfAccountsService->deleteAccount($this->account);
        } catch (AccountHasTransactionsException $exception) {
            $this->toastError($exception->getMessage());

            return;
        }

        $this->toastSuccess(__('Account :code deleted successfully.', ['code' => $code]));

        $this->redirectRoute('accounting.accounts.index', navigate: true);
    }

    public function render(ChartOfAccountsService $chartOfAccountsService, LedgerService $ledgerService): View
    {
        $this->account->load('parent');

        $financialYear = business_context()->financialYear();
        $balance = null;

        if ($financialYear !== null) {
            $balance = $ledgerService->signedBalance($this->account, $financialYear);
        }

        return view('livewire.accounting.account-show', [
            'usage' => $chartOfAccountsService->usageSummary($this->account),
            'locked' => $chartOfAccountsService->lockedAttributes($this->account),
            'children' => $this->account->children()->orderBy('code')->get(),
            'balance' => $balance,
        ]);
    }
}
