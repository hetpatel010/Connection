<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('Payments')"
            :description="__('Track payment receipts and disbursements against invoices and bills.')"
        />
        <livewire:payments.payments-index />
    </x-page-shell>
</x-app-layout>
