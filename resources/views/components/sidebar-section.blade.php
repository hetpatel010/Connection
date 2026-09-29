@props([
    'title',
    'open' => false,
])

<div x-data="{ open: @js($open) }" class="py-0.5">
    <button
        type="button"
        @click="open = !open"
        class="flex w-full items-center justify-between rounded-lg px-2.5 py-3 text-left transition hover:bg-white/5"
    >
        <span class="text-sm font-semibold uppercase tracking-[0.1em] text-slate-400">{{ $title }}</span>
        <svg
            class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-200"
            :class="open && 'rotate-180'"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <div x-show="open" x-cloak class="mt-0.5 space-y-0.5 pb-1.5">
        {{ $slot }}
    </div>
</div>
