<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Edit Product')" />
        <livewire:masters.product-form :product="$product" />
    </x-page-shell>
</x-app-layout>
