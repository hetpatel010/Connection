<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        business_context()->clear();
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="z-20 shrink-0 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-4 lg:hidden">
            <button
                type="button"
                x-on:click="sidebarOpen = true"
                :aria-expanded="sidebarOpen ? 'true' : 'false'"
                aria-label="{{ __('Open navigation menu') }}"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
            </button>

            <livewire:layout.app-brand theme="light" />
        </div>

        <div class="hidden flex-1 items-center justify-center md:flex">
            <button
                type="button"
                x-data
                x-on:click="window.dispatchEvent(new CustomEvent('open-global-search'))"
                class="inline-flex w-full max-w-md items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/80 px-3.5 py-2 text-left text-sm text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-white hover:text-slate-600"
            >
                <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <span class="flex-1 truncate">{{ __('Jump to…') }}</span>
                <x-kbd-shortcut action="search" />
            </button>
        </div>

        <div class="hidden items-center gap-3 md:flex">
            <livewire:layout.business-switcher />
            <livewire:layout.financial-year-selector />
        </div>

        <div class="flex items-center gap-3 md:hidden">
            <button
                type="button"
                x-data
                x-on:click="window.dispatchEvent(new CustomEvent('open-global-search'))"
                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:border-slate-300 hover:text-slate-700"
                aria-label="{{ __('Jump to…') }}"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </button>
        </div>

        <div class="flex items-center gap-3">
            <div class="hidden text-right lg:block">
                <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
            </div>

            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-500">
                        {{ auth()->user()->initials() }}
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile')" wire:navigate>
                        {{ __('Profile') }}
                    </x-dropdown-link>

                    <x-dropdown-link :href="route('about')" wire:navigate>
                        {{ __('About & support') }}
                    </x-dropdown-link>

                    <button wire:click="logout" class="w-full text-start">
                        <x-dropdown-link>
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </button>
                </x-slot>
            </x-dropdown>

            <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 lg:hidden">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="border-t border-slate-200 px-4 py-3 lg:hidden">
        <div class="space-y-3">
            <livewire:layout.business-switcher />
            <livewire:layout.financial-year-selector />
        </div>
    </div>
</nav>
