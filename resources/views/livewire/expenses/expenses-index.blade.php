<div>
    <x-index-toolbar export-module="expenses" :export-search="$search">
        <x-slot:search>
            <x-search-input wire:model.live.debounce.300ms="search" :placeholder="__('Search expenses...')" />
        </x-slot:search>
        <x-slot:actions>
            @can('create', App\Models\Expense::class)
                <x-link-button href="{{ route('expenses.create') }}" wire:navigate>
                    {{ __('New Expense') }}
                </x-link-button>
            @endcan
        </x-slot:actions>
    </x-index-toolbar>

    <x-card flush>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="table-head-cell">{{ __('Number') }}</th>
                        <th class="table-head-cell">{{ __('Date') }}</th>
                        <th class="table-head-cell">{{ __('Account') }}</th>
                        <th class="table-head-cell">{{ __('Description') }}</th>
                        <th class="table-head-cell">{{ __('Status') }}</th>
                        <th class="table-head-cell text-right">{{ __('Amount') }}</th>
                        <th class="table-head-cell"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($expenses as $expense)
                        <tr wire:key="expense-{{ $expense->id }}" class="table-row">
                            <td class="table-cell font-mono font-medium text-slate-900">{{ $expense->expense_number }}</td>
                            <td class="table-cell text-slate-600">{{ $expense->expense_date->format('d M Y') }}</td>
                            <td class="table-cell text-slate-600">{{ $expense->expenseAccount->name }}</td>
                            <td class="table-cell text-slate-900">{{ $expense->description }}</td>
                            <td class="table-cell">
                                <x-status-badge :status="$expense->status->value" />
                            </td>
                            <td class="table-cell text-right font-mono text-slate-900">{{ \App\Support\Money::format($expense->amount + $expense->tax_amount) }}</td>
                            <td class="table-cell text-right">
                                @if ($expense->status->isConfirmed())
                                    @can('reverse', $expense)
                                        <button type="button" wire:click="reverseExpense('{{ $expense->id }}')" wire:confirm="{{ __('Reverse this expense? Accounting entries will be undone.') }}" class="text-sm font-medium text-red-600 transition hover:text-red-500">{{ __('Reverse') }}</button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state :title="__('No expenses found.')" :colspan="7" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $expenses->links() }}</div>
</div>
