<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('Products')"
            :description="__('Maintain your product catalog, pricing, and tax settings.')"
        />
        <livewire:masters.products-index />
    </x-page-shell>
</x-app-layout>
