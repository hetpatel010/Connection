<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('Profile')"
            :description="__('Manage your account settings and security.')"
        >
            <x-slot:actions>
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-shortcuts-help')"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 shadow-sm transition hover:bg-slate-50"
                >
                    {{ __('Shortcuts') }}
                    <x-kbd-shortcut action="help" />
                </button>
            </x-slot:actions>
        </x-page-header>

        <div class="mx-auto max-w-2xl space-y-6" data-shortcut-scope="profile">
            <x-card>
                <div class="flex items-center gap-4 p-6 sm:p-8">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-xl font-bold text-white shadow-lg shadow-indigo-600/30">
                        {{ auth()->user()->initials() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-semibold text-slate-900">{{ auth()->user()->name }}</h2>
                        <p class="truncate text-sm text-slate-500">{{ auth()->user()->email }}</p>
                        @if (business_context()->business())
                            <p class="mt-1.5 text-xs text-slate-400">
                                {{ business_context()->business()->name }}
                                @if (business_context()->financialYear())
                                    · FY {{ business_context()->financialYear()->label }}
                                @endif
                            </p>
                        @endif
                    </div>
                    <a href="{{ route('about') }}" wire:navigate class="hidden shrink-0 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 sm:inline-flex">
                        {{ __('Support') }}
                    </a>
                </div>
            </x-card>

            <x-settings-panel>
                <livewire:profile.update-profile-information-form />
                <livewire:profile.update-password-form />
                <livewire:profile.delete-user-form />
            </x-settings-panel>
        </div>
    </x-page-shell>
</x-app-layout>
