<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Edit Supplier')" />
        <livewire:masters.party-form party-type="supplier" :party="$party" />
    </x-page-shell>
</x-app-layout>
