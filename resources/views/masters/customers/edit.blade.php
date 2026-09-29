<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Edit Customer')" />
        <livewire:masters.party-form party-type="customer" :party="$party" />
    </x-page-shell>
</x-app-layout>
