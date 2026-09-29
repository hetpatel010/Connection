<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Add Account')" :description="__('Create a ledger account or a new group header.')" />
        <livewire:accounting.account-form />
    </x-page-shell>
</x-app-layout>
