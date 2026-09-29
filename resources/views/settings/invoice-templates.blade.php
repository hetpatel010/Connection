<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('Invoice Templates')"
            :description="__('Choose default print layouts for GST tax invoices and non-GST bills. You can override the template on each invoice.')"
        />
        <livewire:settings.invoice-templates-settings />
    </x-page-shell>
</x-app-layout>
