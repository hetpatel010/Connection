<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Payment In')" :description="__('Record a customer receipt.')" />
        <livewire:payments.create-payment payment-type="payment_in" />
    </x-page-shell>
</x-app-layout>
