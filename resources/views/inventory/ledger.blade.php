<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Inventory / Stock Ledger')" :description="$product->name.($product->sku ? ' ('.$product->sku.')' : '').' — '.__('stock movement history by product').'.'">
            <x-slot:actions>
                <a href="{{ route('inventory.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    &larr; {{ __('Back to Inventory') }}
                </a>
            </x-slot:actions>
        </x-page-header>
        <livewire:inventory.stock-ledger :$product />
    </x-page-shell>
</x-app-layout>