<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Inventory')" :description="__('Monitor stock levels across tracked products.')" />
        <livewire:inventory.inventory-index />
    </x-page-shell>
</x-app-layout>
