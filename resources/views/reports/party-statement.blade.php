<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Party Statement')" :description="__('View running balance for a customer or supplier.')" />
        <livewire:reports.party-statement-report />
    </x-page-shell>
</x-app-layout>
