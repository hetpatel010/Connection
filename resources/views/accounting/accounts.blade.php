<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Chart of Accounts')" :description="__('Manage ledger accounts and group headers for this business.')" />
        <livewire:accounting.accounts-index />
    </x-page-shell>
</x-app-layout>
