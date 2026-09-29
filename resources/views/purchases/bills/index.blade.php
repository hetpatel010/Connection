<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Purchase Bills')" :description="__('Track supplier bills and payables.')" />
        <livewire:purchases.purchase-bills-index />
    </x-page-shell>
</x-app-layout>
