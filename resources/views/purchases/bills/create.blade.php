<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('New Purchase Bill')" :description="__('Record a supplier purchase bill.')" />
        <livewire:purchases.create-purchase-bill />
    </x-page-shell>
</x-app-layout>
