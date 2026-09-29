<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Bank Reconciliation')" :description="__('Match bank statement lines with ledger entries.')" />
        <livewire:accounting.bank-reconciliation />
    </x-page-shell>
</x-app-layout>
