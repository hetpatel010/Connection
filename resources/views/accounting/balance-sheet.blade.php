<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Balance Sheet')" :description="__('View assets, liabilities, and equity.')" />
        <livewire:accounting.balance-sheet-report />
    </x-page-shell>
</x-app-layout>
