<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Adjust Stock')" :description="__('Correct on-hand quantities with audit trail.')" />
        <livewire:inventory.adjust-stock :$product />
    </x-page-shell>
</x-app-layout>
