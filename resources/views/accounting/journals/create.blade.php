<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('New Journal Entry')" :description="__('Post a manual accounting journal.')" />
        <livewire:accounting.create-journal />
    </x-page-shell>
</x-app-layout>
