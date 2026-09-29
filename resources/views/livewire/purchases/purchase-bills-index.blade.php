<div>
    <x-index-toolbar export-module="purchase-bills" :export-search="$search">
        <x-slot:search>
            <x-search-input wire:model.live.debounce.300ms="search" :placeholder="__('Search bills...')" />
        </x-slot:search>
        <x-slot:actions>
            @can('create', App\Models\PurchaseBill::class)
                <x-link-button href="{{ route('purchases.bills.create') }}" wire:navigate>
                    {{ __('New Bill') }}
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
                        <th class="table-head-cell">{{ __('Supplier') }}</th>
                        <th class="table-head-cell">{{ __('Status') }}</th>
                        <th class="table-head-cell text-right">{{ __('Total') }}</th>
                        <th class="table-head-cell text-right">{{ __('Outstanding') }}</th>
                        <th class="table-head-cell"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($bills as $bill)
                        @php
                            $allocated = $allocatedAmounts[$bill->id] ?? 0;
                            $outstanding = $bill->status->isConfirmed() && $bill->settlement_mode->isCredit()
                                ? max(0, $bill->total_amount - $allocated)
                                : 0;
                        @endphp
                        <tr wire:key="bill-{{ $bill->id }}" class="table-row">
                            <td class="table-cell font-mono font-medium text-slate-900">{{ $bill->bill_number }}</td>
                            <td class="table-cell text-slate-600">{{ $bill->bill_date->format('d M Y') }}</td>
                            <td class="table-cell text-slate-900">{{ $bill->party->name }}</td>
                            <td class="table-cell">
                                <x-status-badge :status="$bill->status->value" />
                            </td>
                            <td class="table-cell text-right font-mono text-slate-900">{{ \App\Support\Money::format($bill->total_amount) }}</td>
                            <td class="table-cell text-right font-mono {{ $outstanding > 0 ? 'font-medium text-amber-700' : 'text-slate-500' }}">
                                {{ $outstanding > 0 ? \App\Support\Money::format($outstanding) : '—' }}
                            </td>
                            <td class="table-cell text-right">
                                @if ($bill->status->isEditable())
                                    <div class="flex flex-wrap justify-end gap-3">
                                        @can('update', $bill)
                                            <x-table-link href="{{ route('purchases.bills.edit', $bill) }}" wire:navigate>{{ __('Edit') }}</x-table-link>
                                        @endcan
                                        @can('confirm', $bill)
                                            <button type="button" wire:click="confirmDraft('{{ $bill->id }}')" class="text-sm font-medium text-emerald-600 transition hover:text-emerald-500">{{ __('Confirm') }}</button>
                                        @endcan
                                        @can('delete', $bill)
                                            <button type="button" wire:click="deleteDraft('{{ $bill->id }}')" wire:confirm="{{ __('Delete this draft bill?') }}" class="text-sm font-medium text-red-600 transition hover:text-red-500">{{ __('Delete') }}</button>
                                        @endcan
                                    </div>
                                @elseif ($bill->status->isConfirmed())
                                    <div class="flex flex-wrap justify-end gap-3">
                                        <x-table-link href="{{ route('purchases.bills.print', $bill) }}" target="_blank">{{ __('Print') }}</x-table-link>
                                        <x-table-link href="{{ route('purchases.bills.download', $bill) }}">{{ __('Download') }}</x-table-link>
                                        @can('reverse', $bill)
                                            <button type="button" wire:click="reverseBill('{{ $bill->id }}')" wire:confirm="{{ __('Reverse this bill? Accounting entries will be undone.') }}" class="text-sm font-medium text-red-600 transition hover:text-red-500">{{ __('Reverse') }}</button>
                                        @endcan
                                        @can('create', App\Models\DebitNote::class)
                                            <button type="button" wire:click="issueDebitNote('{{ $bill->id }}')" wire:confirm="{{ __('Issue a debit note for this bill? Stock will be returned and accounts adjusted.') }}" class="text-sm font-medium text-amber-600 transition hover:text-amber-500">{{ __('Debit Note') }}</button>
                                        @endcan
                                        @can('cancel', $bill)
                                            <button type="button" wire:click="cancelBill('{{ $bill->id }}')" wire:confirm="{{ __('Cancel this bill? Accounting entries will be undone.') }}" class="text-sm font-medium text-red-600 transition hover:text-red-500">{{ __('Cancel') }}</button>
                                        @endcan
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state :title="__('No purchase bills found.')" :colspan="7" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $bills->links() }}</div>
</div>
