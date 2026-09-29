<x-app-layout>
    <x-page-shell>
        <x-page-header :title="__('Team Members')" :description="__('Invite users and manage business access.')" />
        <livewire:settings.users-index />
    </x-page-shell>
</x-app-layout>
