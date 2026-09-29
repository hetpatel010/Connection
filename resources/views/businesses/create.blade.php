<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('Add shop or brand')"
            :description="__('Create another business profile under your account. Each shop has its own customers, inventory, invoices, and books. Switch between them using the header dropdown.')"
        />
        <livewire:business.create-business />
    </x-page-shell>
</x-app-layout>
