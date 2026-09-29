@props([
    'label',
    'value',
    'tone' => 'default',
])

@php
$toneClasses = match ($tone) {
    'success' => 'from-emerald-500/10 to-emerald-600/5 ring-emerald-500/20',
    'warning' => 'from-amber-500/10 to-amber-600/5 ring-amber-500/20',
    'brand' => 'from-indigo-500/10 to-indigo-600/5 ring-indigo-500/20',
    default => 'from-slate-500/10 to-slate-600/5 ring-slate-500/15',
};
@endphp

<div {{ $attributes->merge(['class' => "relative overflow-hidden rounded-2xl bg-gradient-to-br p-5 shadow-sm ring-1 ring-inset {$toneClasses}"]) }}>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 truncate text-2xl font-bold tracking-tight font-mono text-slate-900">{{ $value }}</p>
        </div>
        @isset($icon)
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/70 shadow-sm ring-1 ring-inset ring-white/60">
                {{ $icon }}
            </div>
        @endisset
    </div>
</div>
