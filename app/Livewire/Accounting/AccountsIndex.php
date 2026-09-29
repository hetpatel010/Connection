<?php

namespace App\Livewire\Accounting;

use App\Application\Services\ChartOfAccountsService;
use App\Domain\Accounting\Enums\AccountStatus;
use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Exceptions\AccountHasTransactionsException;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Account;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class AccountsIndex extends Component
{
    use DispatchesToasts;
    use WithPagination;

    public const PER_PAGE = 15;

    public const VIEWS = ['all', 'postable', 'groups'];

    public string $search = '';

    public string $type = '';

    public string $group = '';

    public string $status = '';

    public string $view = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updating(string $property): void
    {
        if (in_array($property, ['type', 'group', 'status', 'view'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->group = '';
        $this->status = '';
        $this->view = 'all';

        $this->resetPage();
    }

    public function toggleStatus(string $accountId, ChartOfAccountsService $chartOfAccountsService): void
    {
        $account = Account::query()->findOrFail($accountId);

        $this->authorize('update', $account);

        $next = $account->status === AccountStatus::Active
            ? AccountStatus::Inactive
            : AccountStatus::Active;

        $chartOfAccountsService->setStatus($account, $next);

        $this->toastSuccess($next === AccountStatus::Active
            ? __('Account :code activated successfully.', ['code' => $account->code])
            : __('Account :code deactivated successfully.', ['code' => $account->code]));
    }

    public function deleteAccount(string $accountId, ChartOfAccountsService $chartOfAccountsService): void
    {
        $account = Account::query()->findOrFail($accountId);

        $this->authorize('delete', $account);

        $code = $account->code;

        try {
            $chartOfAccountsService->deleteAccount($account);
        } catch (AccountHasTransactionsException $exception) {
            $this->toastError($exception->getMessage());

            return;
        }

        $this->toastSuccess(__('Account :code deleted successfully.', ['code' => $code]));
    }

    public function render(): View
    {
        $this->authorize('viewAny', Account::class);

        $accounts = Account::query()
            ->with('parent')
            ->withCount('journalLines')
            ->filtered([
                'search' => $this->search,
                'type' => $this->type,
                'group' => $this->group,
                'status' => $this->status,
                'view' => $this->view,
            ])
            ->paginate(self::PER_PAGE);

        return view('livewire.accounting.accounts-index', [
            'accounts' => $accounts,
            'accountTypes' => AccountType::cases(),
            'accountStatuses' => AccountStatus::cases(),
            'groupOptions' => Account::query()
                ->distinct()
                ->orderBy('group')
                ->pluck('group')
                ->all(),
        ]);
    }
}
