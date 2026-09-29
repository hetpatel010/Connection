<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-document-head :title="$title ?? ($pageTitle ?? null)" />
    </head>
    <body class="overflow-hidden font-sans antialiased bg-slate-50 text-slate-900">
        <div class="app-shell" x-data="{ sidebarOpen: false }">
            <x-sidebar />

            <div
                class="app-backdrop"
                x-show="sidebarOpen"
                x-cloak
                x-on:click="sidebarOpen = false"
                x-transition:enter="transition-opacity ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                aria-hidden="true"
            ></div>

            <div class="flex min-h-0 min-w-0 flex-col overflow-hidden">
                <livewire:layout.navigation />

                @if (isset($header))
                    <header class="shrink-0 border-b border-slate-200/80 bg-white/80 backdrop-blur-sm">
                        <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main class="main-surface min-h-0 flex-1 overflow-y-auto overscroll-y-contain">
                    {{ $slot }}
                </main>

                <x-app-footer />
            </div>
        </div>

        <x-toast-container />

        @auth
            <x-global-search />
        @endauth

        @livewireScripts
        <x-keyboard-shortcuts-help />
    </body>
</html>
