<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('GST Summary')" :description="__('Review GST collected and paid.')" />
        <livewire:accounting.gst-summary-report />
    </x-page-shell>
</x-app-layout>
