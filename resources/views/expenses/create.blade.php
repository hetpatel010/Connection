<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('New Expense')" :description="__('Record a business expense.')" />
        <livewire:expenses.create-expense />
    </x-page-shell>
</x-app-layout>
