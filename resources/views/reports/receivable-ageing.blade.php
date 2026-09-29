<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Receivable Ageing')" :description="__('Analyze overdue customer receivables.')" />
        <livewire:reports.receivable-ageing-report />
    </x-page-shell>
</x-app-layout>
