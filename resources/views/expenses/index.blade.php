<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Expenses')" :description="__('Record and review business expenses.')" />
        <livewire:expenses.expenses-index />
    </x-page-shell>
</x-app-layout>
