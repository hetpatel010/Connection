<?php

namespace App\Livewire\Expenses;

use App\Application\Services\ExpenseService;
use App\Domain\Accounting\Enums\AccountType;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Account;
use App\Models\Expense;
use App\Models\Party;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateExpense extends Component
{
    use DispatchesToasts;

    public string $expenseDate = '';

    public string $expenseAccountId = '';

    public string $paymentAccountId = '';

    public string $partyId = '';

    public string $amount = '';

    public string $taxAmount = '0';

    public string $description = '';

    public string $reference = '';

    public function mount(): void
    {
        $this->authorize('create', Expense::class);
        $this->expenseDate = now()->toDateString();
    }

    public function save(ExpenseService $expenseService): void
    {
        $this->authorize('create', Expense::class);

        $validated = $this->validate([
            'expenseDate' => ['required', 'date'],
            'expenseAccountId' => ['required', 'uuid'],
            'paymentAccountId' => ['required', 'uuid'],
            'partyId' => ['nullable', 'uuid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'taxAmount' => ['nullable', 'numeric', 'min:0'],
            'description' => ['required', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $expense = $expenseService->createAndConfirm(
            business: business_context()->business(),
            financialYear: business_context()->financialYear(),
            user: auth()->user(),
            attributes: [
                'expense_date' => $validated['expenseDate'],
                'expense_account_id' => $validated['expenseAccountId'],
                'payment_account_id' => $validated['paymentAccountId'],
                'party_id' => $validated['partyId'] ?: null,
                'amount' => Money::rupeesToPaise($validated['amount']),
                'tax_amount' => Money::rupeesToPaise($validated['taxAmount'] ?: 0),
                'description' => $validated['description'],
                'reference' => $validated['reference'] ?: null,
            ],
        );

        $this->toastSuccess(__('Expense :number recorded.', ['number' => $expense->expense_number]));

        $this->redirectRoute('expenses.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.expenses.create-expense', [
            'expenseAccounts' => Account::query()
                ->where('type', AccountType::Expense)
                ->whereNotNull('parent_id')
                ->orderBy('code')
                ->get(),
            'paymentAccounts' => Account::query()->whereIn('code', ['1100', '1200'])->orderBy('code')->get(),
            'suppliers' => Party::query()->suppliers()->orderBy('name')->get(),
        ]);
    }
}
