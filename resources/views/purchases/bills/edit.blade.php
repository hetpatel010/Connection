<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Edit Purchase Bill')" />
        <livewire:purchases.edit-purchase-bill :bill="$bill" />
    </x-page-shell>
</x-app-layout>
