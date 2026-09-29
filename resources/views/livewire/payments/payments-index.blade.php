<div>
    <x-index-toolbar export-module="payments" :export-search="$search" :export-type="$type">
        <x-slot:search>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-search-input wire:model.live.debounce.300ms="search" :placeholder="__('Search payments...')" />
                <x-select-input wire:model.live="type" class="w-full sm:w-auto">
                    <option value="">{{ __('All types') }}</option>
                    <option value="payment_in">{{ __('Payment In') }}</option>
                    <option value="payment_out">{{ __('Payment Out') }}</option>
                </x-select-input>
            </div>
        </x-slot:search>
        <x-slot:actions>
            @can('create', App\Models\Payment::class)
                <x-link-button href="{{ route('payments.in.create') }}" wire:navigate>{{ __('Payment In') }}</x-link-button>
                <a href="{{ route('payments.out.create') }}" wire:navigate class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-slate-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
                    {{ __('Payment Out') }}
                </a>
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
                        <th class="table-head-cell">{{ __('Type') }}</th>
                        <th class="table-head-cell">{{ __('Party') }}</th>
                        <th class="table-head-cell text-right">{{ __('Amount') }}</th>
                        <th class="table-head-cell text-right">{{ __('Allocated') }}</th>
                        <th class="table-head-cell"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($payments as $payment)
                        @php
                            $allocated = $payment->status->isConfirmed()
                                ? $payment->allocations->sum('allocated_amount')
                                : 0;
                        @endphp
                        <tr wire:key="payment-{{ $payment->id }}" class="table-row">
                            <td class="table-cell font-mono font-medium text-slate-900">{{ $payment->payment_number }}</td>
                            <td class="table-cell text-slate-600">{{ $payment->payment_date->format('d M Y') }}</td>
                            <td class="table-cell text-slate-600">{{ $payment->payment_type->label() }}</td>
                            <td class="table-cell text-slate-900">{{ $payment->party?->name ?? '—' }}</td>
                            <td class="table-cell text-right font-mono text-slate-900">{{ \App\Support\Money::format($payment->amount) }}</td>
                            <td class="table-cell text-right font-mono text-slate-600">
                                {{ $allocated > 0 ? \App\Support\Money::format($allocated) : '—' }}
                            </td>
                            <td class="table-cell text-right">
                                @if ($payment->status->isConfirmed())
                                    @can('reverse', $payment)
                                        <button type="button" wire:click="reversePayment('{{ $payment->id }}')" wire:confirm="{{ __('Reverse this payment? Accounting entries will be undone.') }}" class="text-sm font-medium text-red-600 transition hover:text-red-500">{{ __('Reverse') }}</button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state :title="__('No payments found.')" :colspan="7" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $payments->links() }}</div>
</div>
