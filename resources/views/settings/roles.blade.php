<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Roles & Permissions')" :description="__('Configure access levels for your team.')" />
        <livewire:settings.roles-index />
    </x-page-shell>
</x-app-layout>
