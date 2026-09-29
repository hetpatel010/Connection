<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <x-document-head :title="$title ?? ($pageTitle ?? null)" />
    </head>
    <body class="font-sans antialiased">
        <div class="main-surface flex min-h-screen flex-col items-center justify-center px-4 py-10 sm:px-6">
            <a href="{{ route('login') }}" wire:navigate class="mb-8 block max-w-sm">
                <x-product-mark />
            </a>

            <div class="surface-card w-full max-w-md p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>

        <x-toast-container />

        @livewireScripts
    </body>
</html>
