<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('Sales Invoices')"
            :description="__('Manage and track all customer invoices.')"
        >
            <x-slot:actions>
                @can('create', App\Models\SalesInvoice::class)
                    <x-link-button href="{{ route('sales.invoices.create') }}" wire:navigate>
                        {{ __('New Invoice') }}
                    </x-link-button>
                @endcan
            </x-slot:actions>
        </x-page-header>

        <livewire:sales.invoices-index />
    </x-page-shell>
</x-app-layout>
