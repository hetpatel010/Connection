<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Debit Notes')" :description="__('Review debit notes issued against purchase bills.')" />
        <livewire:purchases.debit-notes-index />
    </x-page-shell>
</x-app-layout>
