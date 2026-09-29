<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Profit & Loss')" :description="__('Analyze income and expenses for the period.')" />
        <livewire:accounting.profit-loss-report />
    </x-page-shell>
</x-app-layout>
