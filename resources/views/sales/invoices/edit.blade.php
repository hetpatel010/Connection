<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Edit Invoice')" :description="__('Update draft invoice details before confirming.')">
            <x-slot:actions>
                <a href="{{ route('sales.invoices.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-600 transition hover:text-slate-900">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    {{ __('Back to invoices') }}
                </a>
            </x-slot:actions>
        </x-page-header>

        <livewire:sales.edit-invoice :invoice="$invoice" />
    </x-page-shell>
</x-app-layout>
