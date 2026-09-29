<div>
    @php
        $unitSymbol = $product->unit?->symbol ?: '';
        $minStock = (float) $product->minimum_stock;
        $isLow = $minStock > 0 && $currentStock <= $minStock;
        $activeFilterCount = collect([$fromDate, $toDate, $type, $reference, $search])->reject(fn ($v) => trim((string) $v) === '')->count();
    @endphp

    {{-- Product summary --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-bold tracking-tight text-slate-900">{{ $product->name }}</h2>
                    @if ($product->sku)
                        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-600">{{ $product->sku }}</span>
                    @endif
                    @if ($isLow)
                        <span class="rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{{ __('Low stock') }}</span>
                    @endif
                </div>

                <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Unit') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $unitSymbol ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Current Stock') }}</dt>
                        <dd class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $currentStock + 0 }} {{ $unitSymbol }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Minimum Stock') }}</dt>
                        <dd class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $minStock + 0 ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Category') }}</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $product->category?->name ?: '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <a href="{{ route('inventory.ledger.export', $product) }}@if (request()->getQueryString())?{{ request()->getQueryString() }}@endif"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('Export Ledger Excel') }}
                </a>
                <a href="{{ route('inventory.adjust', $product) }}" wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-500">
                    {{ __('Adjust Stock') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card :label="__('Opening Stock')" :value="($totals['opening'] + 0).' '.$unitSymbol" />
        <x-stat-card :label="__('Total Stock In')" :value="'+'.($totals['in'] + 0).' '.$unitSymbol" tone="success" />
        <x-stat-card :label="__('Total Stock Out')" :value="'-'.($totals['out'] + 0).' '.$unitSymbol" tone="warning" />
        <x-stat-card :label="__('Current Balance')" :value="($totals['balance'] + 0).' '.$unitSymbol" tone="brand" />
    </div>

    {{-- Filters --}}
    <form wire:submit="applyFilters" class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <x-input-label for="ledger-from" :value="__('From Date')" />
                <x-text-input wire:model.live="fromDate" id="ledger-from" type="date" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="ledger-to" :value="__('To Date')" />
                <x-text-input wire:model.live="toDate" id="ledger-to" type="date" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="ledger-type" :value="__('Transaction Type')" />
                <x-select-input wire:model.live="type" id="ledger-type" class="mt-1 block w-full">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach ($typeOptions as $option)
                        <option value="{{ $option['value'] }}">{{ __($option['label']) }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="ledger-reference" :value="__('Reference Number')" />
                <x-text-input wire:model.live="reference" id="ledger-reference" type="text" class="mt-1 block w-full" :placeholder="__('Filter by reference...')" />
            </div>
            <div>
                <x-input-label for="ledger-search" :value="__('Search')" />
                <x-text-input wire:model.live.debounce.300ms="search" id="ledger-search" type="search" class="mt-1 block w-full" :placeholder="__('Reference, description, type...')" />
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">
                @if ($activeFilterCount > 0)
                    {{ trans_choice(':count filter applied.|:count filters applied.', $activeFilterCount, ['count' => $activeFilterCount]) }}
                @else
                    {{ __('Showing all movements') }}
                @endif
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <x-secondary-button type="button" wire:click="resetFilters">{{ __('Reset Filter') }}</x-secondary-button>
                <x-primary-button type="submit">{{ __('Apply Filter') }}</x-primary-button>
            </div>
        </div>
    </form>

    {{-- Transactions --}}
    @if ($movements->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <x-empty-state
                :title="__('No stock transactions found')"
                :description="$hasAnyMovements ? __('No movements match the current filters.') : __('This product does not have any stock movement yet.')">
                <x-slot:icon>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                </x-slot:icon>
                <x-slot:action>
                    <a href="{{ route('inventory.index') }}" wire:navigate
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                        &larr; {{ __('Back to Inventory') }}
                    </a>
                </x-slot:action>
            </x-empty-state>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="table-head-cell">{{ __('Date') }}</th>
                            <th class="table-head-cell">{{ __('Reference No.') }}</th>
                            <th class="table-head-cell">{{ __('Transaction Type') }}</th>
                            <th class="table-head-cell">{{ __('Description') }}</th>
                            <th class="table-head-cell text-right">{{ __('Stock In') }}</th>
                            <th class="table-head-cell text-right">{{ __('Stock Out') }}</th>
                            <th class="table-head-cell text-right">{{ __('Balance') }}</th>
                            <th class="table-head-cell"><span class="sr-only">{{ __('Action') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($movements as $movement)
                            @php
                                $actionUrl = $this->documentAction($movement);
                            @endphp
                            <tr wire:key="movement-{{ $movement->id }}" class="table-row">
                                <td class="table-cell whitespace-nowrap text-slate-600">{{ $movement->movement_date->format('d M Y') }}</td>
                                <td class="table-cell whitespace-nowrap font-mono text-slate-700">{{ $movement->reference ?? '—' }}</td>
                                <td class="table-cell whitespace-nowrap">
                                    <span @class([
                                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-emerald-50 text-emerald-700' => $movement->isIncoming(),
                                        'bg-rose-50 text-rose-700' => ! $movement->isIncoming(),
                                    ])>
                                        {{ $movement->ledgerLabel() }}
                                    </span>
                                </td>
                                <td class="table-cell max-w-xs truncate text-slate-600" title="{{ $movement->notes ?? '' }}">{{ $movement->notes ?? '—' }}</td>
                                <td class="table-cell whitespace-nowrap text-right font-mono text-emerald-700">
                                    {{ $movement->isIncoming() ? '+'.($movement->quantity + 0) : '—' }}
                                </td>
                                <td class="table-cell whitespace-nowrap text-right font-mono text-rose-700">
                                    {{ ! $movement->isIncoming() ? '-'.($movement->quantity + 0) : '—' }}
                                </td>
                                <td class="table-cell whitespace-nowrap text-right font-mono font-semibold text-slate-900">
                                    {{ $movement->balance + 0 }}
                                </td>
                                <td class="table-cell whitespace-nowrap text-right">
                                    @if ($actionUrl !== null)
                                        <x-table-link :href="$actionUrl" wire:navigate>{{ __('View') }}</x-table-link>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <x-empty-state :title="__('No stock transactions found')" :description="__('This product does not have any stock movement yet.')" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    @endif
</div>