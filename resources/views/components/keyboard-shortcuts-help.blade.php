<div
    x-data="{ open: false }"
    x-on:open-shortcuts-help.window="open = true"
    x-on:close-shortcuts-help.window="open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[65] flex items-end justify-center p-4 sm:items-center"
    style="display: none;"
>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" x-on:click="open = false"></div>

    <div
        x-show="open"
        x-transition
        class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5"
        role="dialog"
        aria-labelledby="shortcuts-title"
    >
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 id="shortcuts-title" class="text-base font-semibold text-slate-900">{{ __('Keyboard shortcuts') }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ __('Works on Mac (⌘) and Windows/Linux (Ctrl).') }}</p>
        </div>

        <ul class="divide-y divide-slate-100 px-5 py-2 text-sm">
            <li class="flex items-center justify-between gap-4 py-3">
                <span class="text-slate-700">{{ __('Save form / draft') }}</span>
                <x-kbd-shortcut action="save" />
            </li>
            <li class="flex items-center justify-between gap-4 py-3">
                <span class="text-slate-700">{{ __('Confirm / primary action') }}</span>
                <x-kbd-shortcut action="submit" />
            </li>
            <li class="flex items-center justify-between gap-4 py-3">
                <span class="text-slate-700">{{ __('Quick jump / command palette') }}</span>
                <x-kbd-shortcut action="search" />
            </li>
            <li class="flex items-center justify-between gap-4 py-3">
                <span class="text-slate-700">{{ __('Focus list search') }}</span>
                <kbd class="kbd">/</kbd>
            </li>
            <li class="flex items-center justify-between gap-4 py-3">
                <span class="text-slate-700">{{ __('Show this help') }}</span>
                <x-kbd-shortcut action="help" />
            </li>
            <li class="flex items-center justify-between gap-4 py-3">
                <span class="text-slate-700">{{ __('Close dialogs') }}</span>
                <kbd class="kbd">Esc</kbd>
            </li>
        </ul>

        <div class="border-t border-slate-100 bg-slate-50/60 px-5 py-3 text-center text-xs text-slate-500">
            {{ __('Press') }} <x-kbd-shortcut action="help" /> {{ __('anytime to reopen') }}
        </div>
    </div>
</div>
