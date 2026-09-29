<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('Export Data')"
            :description="__('Download your business records for the active financial year.')"
        />
        <livewire:settings.data-export />
    </x-page-shell>
</x-app-layout>
