<?php

namespace App\Livewire\Accounting;

use App\Application\Services\ChartOfAccountsService;
use App\Domain\Accounting\Enums\AccountStatus;
use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\BalanceSide;
use App\Domain\Accounting\Exceptions\AccountHasTransactionsException;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Account;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AccountForm extends Component
{
    use DispatchesToasts;

    public ?Account $account = null;

    public string $code = '';

    public string $name = '';

    public string $type = '';

    public string $group = '';

    public string $parentId = '';

    public string $openingBalance = '0';

    public string $openingBalanceSide = '';

    public string $status = '';

    public string $description = '';

    public function mount(?Account $account = null): void
    {
        if ($account !== null && $account->exists) {
            $this->authorize('update', $account);

            $this->account = $account;
            $this->fillFromAccount($account);
        } else {
            $this->authorize('create', Account::class);

            $this->status = AccountStatus::Active->value;
            $this->type = AccountType::Asset->value;
        }
    }

    public function save(ChartOfAccountsService $chartOfAccountsService): void
    {
        if ($this->account === null) {
            $this->authorize('create', Account::class);
        } else {
            $this->authorize('update', $this->account);
        }

        $locked = $this->account === null
            ? ['code' => false, 'type' => false, 'opening_balance' => false]
            : $chartOfAccountsService->lockedAttributes($this->account);

        $validated = $this->validate([
            'code' => $locked['code']
                ? ['nullable']
                : ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9.\-\/]+$/', Rule::unique('accounts', 'code')->where('business_id', business_context()->businessId())->ignore($this->account?->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => $locked['type']
                ? ['nullable']
                : ['required', Rule::enum(AccountType::class)],
            'group' => ['nullable', 'string', 'max:255'],
            'parentId' => ['nullable', 'uuid', Rule::exists('accounts', 'id')->where('business_id', business_context()->businessId())],
            'openingBalance' => $locked['opening_balance'] ? ['nullable'] : ['nullable', 'numeric', 'min:0'],
            'openingBalanceSide' => $locked['opening_balance'] ? ['nullable'] : ['nullable', Rule::enum(BalanceSide::class)],
            'status' => ['required', Rule::enum(AccountStatus::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $parent = $this->resolveParent();
        $openingBalancePaise = Money::rupeesToPaise($validated['openingBalance'] ?: 0);

        $payload = [
            'name' => $validated['name'],
            'group' => $this->resolveGroup($validated['group'] ?? '', $parent, $validated['type'] ?? null),
            'status' => $validated['status'],
            'description' => $validated['description'] ?: null,
            'parent_id' => $parent?->id,
        ];

        if (! $locked['code']) {
            $payload['code'] = $validated['code'];
        }

        if (! $locked['type']) {
            $payload['type'] = $validated['type'];
        }

        if (! $locked['opening_balance']) {
            $payload['opening_balance'] = $openingBalancePaise;
            $payload['opening_balance_side'] = $openingBalancePaise > 0
                ? ($validated['openingBalanceSide'] ?: BalanceSide::Debit->value)
                : null;
        }

        $business = business_context()->business();

        try {
            if ($this->account === null) {
                $chartOfAccountsService->createAccount($business, $payload);

                $this->toastSuccess(__('Account created successfully.'));
            } else {
                $chartOfAccountsService->updateAccount($this->account, $payload);

                $this->toastSuccess(__('Account updated successfully.'));
            }
        } catch (AccountHasTransactionsException $exception) {
            $this->toastError($exception->getMessage());

            return;
        }

        $this->redirectRoute('accounting.accounts.index', navigate: true);
    }

    public function updatedType(): void
    {
        if ($this->group === '' && $this->type !== '') {
            $this->group = AccountType::tryFrom($this->type)?->groupLabel() ?? '';
        }
    }

    public function updatedParentId(): void
    {
        $parent = $this->resolveParent();

        if ($parent !== null) {
            $this->group = $parent->group;
            $this->type = $parent->type->value;
        }
    }

    public function render(ChartOfAccountsService $chartOfAccountsService): View
    {
        return view('livewire.accounting.account-form', [
            'accountTypes' => AccountType::cases(),
            'accountStatuses' => AccountStatus::cases(),
            'balanceSides' => BalanceSide::cases(),
            'parentOptions' => $this->parentOptions(),
            'locked' => $this->account === null
                ? ['code' => false, 'type' => false, 'opening_balance' => false]
                : $chartOfAccountsService->lockedAttributes($this->account),
        ]);
    }

    /**
     * @return Collection<int, Account>
     */
    private function parentOptions(): Collection
    {
        return Account::query()
            ->whereNull('parent_id')
            ->when($this->account !== null, fn ($query) => $query->where('id', '!=', $this->account->id))
            ->orderBy('code')
            ->get();
    }

    private function resolveParent(): ?Account
    {
        if ($this->parentId === '') {
            return null;
        }

        return Account::query()->find($this->parentId);
    }

    private function resolveGroup(string $group, ?Account $parent, ?string $type): string
    {
        if ($group !== '') {
            return $group;
        }

        if ($parent !== null) {
            return $parent->group;
        }

        return AccountType::tryFrom($type ?? '')?->groupLabel() ?? '';
    }

    private function fillFromAccount(Account $account): void
    {
        $this->code = $account->code;
        $this->name = $account->name;
        $this->type = $account->type->value;
        $this->group = $account->group;
        $this->parentId = $account->parent_id ?? '';
        $this->openingBalance = (string) Money::paiseToRupees($account->opening_balance);
        $this->openingBalanceSide = $account->opening_balance_side?->value ?? '';
        $this->status = $account->status->value;
        $this->description = $account->description ?? '';
    }
}
