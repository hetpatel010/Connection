<div>
    @if ($lowStockCount > 0)
        <div class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ trans_choice(':count product is below minimum stock.|:count products are below minimum stock.', $lowStockCount, ['count' => $lowStockCount]) }}
        </div>
    @endif

    <x-index-toolbar export-module="inventory" :export-search="$search">
        <x-slot:search>
            <x-search-input wire:model.live.debounce.300ms="search" :placeholder="__('Search products...')" />
        </x-slot:search>
    </x-index-toolbar>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Product') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('SKU') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Unit') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('In Stock') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Min') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($levels as $row)
                    <tr wire:key="stock-{{ $row['product']->id }}" @class(['bg-amber-50' => $row['is_low']])>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $row['product']->name }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $row['product']->sku ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $row['product']->unit?->symbol }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">{{ $row['current_stock'] + 0 }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $row['minimum_stock'] + 0 ?: '—' }}</td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('inventory.ledger', $row['product']) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">{{ __('Ledger') }}</a>
                            <a href="{{ route('inventory.adjust', $row['product']) }}" wire:navigate class="text-indigo-600 hover:text-indigo-800">{{ __('Adjust') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No products found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
