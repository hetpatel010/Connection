<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Add Supplier')" :description="__('Create a new supplier profile.')" />
        <livewire:masters.party-form party-type="supplier" />
    </x-page-shell>
</x-app-layout>
