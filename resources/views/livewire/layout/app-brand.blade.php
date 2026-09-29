@php
    $isDark = $theme === 'dark';
@endphp

<a
    href="{{ route('dashboard') }}"
    wire:navigate
    @class([
        'group block min-w-0 max-w-full transition-opacity hover:opacity-90',
        'rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400/60' => $isDark,
        'rounded-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/40' => ! $isDark,
    ])
>
    @if ($logoUrl)
        <div @class([
            'flex min-w-0 items-center justify-center overflow-hidden rounded-lg',
            'bg-white px-3 py-2.5 shadow-sm ring-1 ring-white/20' => $isDark,
            'bg-slate-50 px-3 py-2.5 ring-1 ring-slate-200/80' => ! $isDark,
        ])>
            <img
                src="{{ $logoUrl }}"
                alt="{{ $brandName }}"
                class="h-10 w-auto max-w-full object-contain object-center"
            >
        </div>
    @else
        <div class="flex min-w-0 items-center gap-2.5">
            <div @class([
                'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-sm font-bold text-white shadow-lg',
                'bg-indigo-500 shadow-indigo-500/30' => $isDark,
                'bg-indigo-600 shadow-indigo-600/20' => ! $isDark,
            ])>
                {{ $initial }}
            </div>

            <span @class([
                'truncate text-lg font-semibold leading-snug tracking-tight',
                'text-white' => $isDark,
                'text-slate-900' => ! $isDark,
            ])>
                {{ $brandName }}
            </span>
        </div>
    @endif
</a>
