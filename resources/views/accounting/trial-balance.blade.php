<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Trial Balance')" :description="__('Review debit and credit balances by account.')" />
        <livewire:accounting.trial-balance-report />
    </x-page-shell>
</x-app-layout>
