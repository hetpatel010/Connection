<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Add Customer')" :description="__('Create a new customer profile.')" />
        <livewire:masters.party-form party-type="customer" />
    </x-page-shell>
</x-app-layout>
