<?php

namespace App\Livewire\Expenses;

use App\Application\Services\ExpenseService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Expense;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ExpensesIndex extends Component
{
    use DispatchesToasts;
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function reverseExpense(string $expenseId, ExpenseService $expenseService): void
    {
        $expense = Expense::query()->findOrFail($expenseId);
        $this->authorize('reverse', $expense);

        $expenseService->reverse($expense, auth()->user());

        $this->toastSuccess(__('Expense :number reversed.', ['number' => $expense->expense_number]));
    }

    public function render(): View
    {
        $this->authorize('viewAny', Expense::class);

        $expenses = Expense::query()
            ->with(['expenseAccount', 'paymentAccount'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('expense_number', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->orderByDesc('expense_date')
            ->paginate(15);

        return view('livewire.expenses.expenses-index', [
            'expenses' => $expenses,
        ]);
    }
}
