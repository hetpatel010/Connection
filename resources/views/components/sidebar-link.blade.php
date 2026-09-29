@props(['active' => false, 'compact' => true])

@php
$classes = $active
    ? 'group flex items-center gap-3 rounded-lg bg-white/10 py-3 pl-2.5 pr-2.5 text-base font-semibold text-white ring-1 ring-inset ring-white/10'
    : 'group flex items-center gap-3 rounded-lg py-3 pl-2.5 pr-2.5 text-base font-medium text-slate-300 transition hover:bg-white/5 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-white/20';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if($active) data-sidebar-active @endif wire:navigate>
    @isset($icon)
        <span @class([
            'flex shrink-0 items-center justify-center rounded-md [&_svg]:h-5 [&_svg]:w-5',
            'h-9 w-9' => $compact,
            'h-10 w-10' => ! $compact,
            'bg-indigo-500/20 text-indigo-300' => $active,
            'bg-slate-800/80 text-slate-400 group-hover:bg-slate-700 group-hover:text-slate-200' => ! $active,
        ])>
            {{ $icon }}
        </span>
    @endisset
    <span class="truncate leading-snug">{{ $slot }}</span>
</a>
