<div
    x-data="{ deleteAction: '' }"
    x-on:product-delete-confirm="deleteAction = $event.detail.url"
>
    <x-index-toolbar export-module="products" :export-search="$search">
        <x-slot:search>
            <x-search-input wire:model.live.debounce.300ms="search" :placeholder="__('Search products...')" />
        </x-slot:search>
        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <x-link-button href="{{ route('masters.products.create') }}" wire:navigate>
                    {{ __('Add Product') }}
                </x-link-button>
            @endcan
        </x-slot:actions>
    </x-index-toolbar>

    <x-card flush>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="table-head-cell">{{ __('Name') }}</th>
                        <th class="table-head-cell">{{ __('SKU') }}</th>
                        <th class="table-head-cell">{{ __('Unit') }}</th>
                        <th class="table-head-cell text-right">{{ __('Sale Price') }}</th>
                        <th class="table-head-cell text-right">{{ __('Tax') }}</th>
                        <th class="table-head-cell"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($products as $product)
                        <tr wire:key="product-{{ $product->id }}" class="table-row">
                            <td class="table-cell font-medium text-slate-900">{{ $product->name }}</td>
                            <td class="table-cell font-mono text-slate-600">{{ $product->sku ?? '—' }}</td>
                            <td class="table-cell text-slate-600">{{ $product->unit?->symbol }}</td>
                            <td class="table-cell text-right font-mono text-slate-900">{{ \App\Support\Money::format($product->selling_price) }}</td>
                            <td class="table-cell text-right text-slate-600">{{ $product->tax_rate }}%</td>
                            <td class="table-cell text-right">
                                <div class="flex items-center justify-end gap-4">
                                    @can('update', $product)
                                        <x-table-link href="{{ route('masters.products.edit', $product) }}" wire:navigate>{{ __('Edit') }}</x-table-link>
                                    @endcan

                                    @can('delete', $product)
                                        <button
                                            type="button"
                                            class="rounded-md text-sm font-medium text-red-600 transition hover:text-red-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                                            x-on:click="$dispatch('product-delete-confirm', { url: '{{ route('masters.products.destroy', $product->id) }}' }); $dispatch('open-modal', 'confirm-product-delete')"
                                        >{{ __('Delete') }}</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state :title="__('No products found.')" :colspan="6" />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <div class="mt-4">{{ $products->links() }}</div>

    <x-modal name="confirm-product-delete" maxWidth="md" focusable>
        <x-slot:header>
            <h2 class="text-lg font-semibold text-slate-900">
                {{ __('Are you sure you want to delete this product?') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('This action cannot be undone.') }}
            </p>
        </x-slot:header>

        <form :action="deleteAction" method="post" id="delete-product-form">
            @csrf
            @method('DELETE')
        </form>

        <x-slot:footer>
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button form="delete-product-form">
                {{ __('Delete Product') }}
            </x-danger-button>
        </x-slot:footer>
    </x-modal>
</div>
