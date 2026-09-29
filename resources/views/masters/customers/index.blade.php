<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('Customers')"
            :description="__('Manage customer profiles, GST details, and billing addresses.')"
        />
        <livewire:masters.customers-index />
    </x-page-shell>
</x-app-layout>
