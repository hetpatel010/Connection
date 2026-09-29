<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Payment Out')" :description="__('Record a supplier payment.')" />
        <livewire:payments.create-payment payment-type="payment_out" />
    </x-page-shell>
</x-app-layout>
