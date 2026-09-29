<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Payable Ageing')" :description="__('Analyze overdue supplier payables.')" />
        <livewire:reports.payable-ageing-report />
    </x-page-shell>
</x-app-layout>
