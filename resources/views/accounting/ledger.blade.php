<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Ledger Report')" :description="__('Inspect account-wise transaction history.')" />
        <livewire:accounting.ledger-report />
    </x-page-shell>
</x-app-layout>
