<div>
    <x-index-toolbar export-module="sales-invoices" :export-search="$search">
        <x-slot:search>
            <x-search-input wire:model.live.debounce.300ms="search" :placeholder="__('Search invoices...')" />
        </x-slot:search>
    </x-index-toolbar>

    <x-card flush>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="table-head-cell">{{ __('Number') }}</th>
                        <th class="table-head-cell">{{ __('Date') }}</th>
                        <th class="table-head-cell">{{ __('Customer') }}</th>
                        <th class="table-head-cell">{{ __('Status') }}</th>
                        <th class="table-head-cell text-right">{{ __('Total') }}</th>
                        <th class="table-head-cell text-right">{{ __('Outstanding') }}</th>
                        <th class="table-head-cell"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($invoices as $invoice)
                        @php
                            $allocated = $allocatedAmounts[$invoice->id] ?? 0;
                            $outstanding = $invoice->status->isConfirmed() && $invoice->settlement_mode->isCredit()
                                ? max(0, $invoice->total_amount - $allocated)
                                : 0;
                        @endphp
                        <tr wire:key="invoice-{{ $invoice->id }}" class="table-row">
                            <td class="table-cell font-mono font-medium text-slate-900">{{ $invoice->invoice_number }}</td>
                            <td class="table-cell text-slate-600">{{ $invoice->invoice_date->format('d M Y') }}</td>
                            <td class="table-cell text-slate-900">{{ $invoice->party->name }}</td>
                            <td class="table-cell">
                                <x-status-badge :status="$invoice->status->value" />
                            </td>
                            <td class="table-cell text-right font-mono text-slate-900">{{ \App\Support\Money::format($invoice->total_amount) }}</td>
                            <td class="table-cell text-right font-mono {{ $outstanding > 0 ? 'font-medium text-amber-700' : 'text-slate-500' }}">
                                {{ $outstanding > 0 ? \App\Support\Money::format($outstanding) : '—' }}
                            </td>
                            <td class="table-cell text-right">
                                @if ($invoice->status->isEditable())
                                    <div class="flex justify-end gap-3">
                                        @can('update', $invoice)
                                            <x-table-link href="{{ route('sales.invoices.edit', $invoice) }}" wire:navigate>{{ __('Edit') }}</x-table-link>
                                        @endcan
                                        @can('confirm', $invoice)
                                            <button type="button" wire:click="confirmDraft('{{ $invoice->id }}')" class="text-sm font-medium text-emerald-600 transition hover:text-emerald-500">{{ __('Confirm') }}</button>
                                        @endcan
                                        @can('delete', $invoice)
                                            <button type="button" wire:click="deleteDraft('{{ $invoice->id }}')" wire:confirm="{{ __('Delete this draft invoice?') }}" class="text-sm font-medium text-red-600 transition hover:text-red-500">{{ __('Delete') }}</button>
                                        @endcan
                                    </div>
                                @elseif ($invoice->status->isConfirmed())
                                    <div class="flex flex-wrap justify-end gap-3">
                                        <x-table-link href="{{ route('sales.invoices.print', $invoice) }}" target="_blank">{{ __('Print') }}</x-table-link>
                                        <x-table-link href="{{ route('sales.invoices.download', $invoice) }}">{{ __('Download') }}</x-table-link>
                                        @can('reverse', $invoice)
                                            <button type="button" wire:click="reverseInvoice('{{ $invoice->id }}')" wire:confirm="{{ __('Reverse this invoice? Accounting entries will be undone.') }}" class="text-sm font-medium text-red-600 transition hover:text-red-500">{{ __('Reverse') }}</button>
                                        @endcan
                                        @can('create', App\Models\CreditNote::class)
                                            <button type="button" wire:click="issueCreditNote('{{ $invoice->id }}')" wire:confirm="{{ __('Issue a credit note for this invoice? Stock will be returned and accounts adjusted.') }}" class="text-sm font-medium text-amber-600 transition hover:text-amber-500">{{ __('Credit Note') }}</button>
                                        @endcan
                                        @can('cancel', $invoice)
                                            <button type="button" wire:click="cancelInvoice('{{ $invoice->id }}')" wire:confirm="{{ __('Cancel this invoice? Accounting entries will be undone.') }}" class="text-sm font-medium text-red-600 transition hover:text-red-500">{{ __('Cancel') }}</button>
                                        @endcan
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state :title="__('No invoices found.')" :colspan="7" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $invoices->links() }}</div>
</div>
