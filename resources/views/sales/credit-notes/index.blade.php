<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Credit Notes')" :description="__('Review credit notes issued against sales invoices.')" />
        <livewire:sales.credit-notes-index />
    </x-page-shell>
</x-app-layout>
