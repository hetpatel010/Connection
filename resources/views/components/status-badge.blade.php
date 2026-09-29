@props([
    'status',
])

@php
    $normalized = strtolower((string) $status);
    $classes = match (true) {
        in_array($normalized, ['active', 'confirmed', 'posted', 'open'], true) => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
        in_array($normalized, ['draft', 'pending'], true) => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        in_array($normalized, ['inactive', 'cancelled', 'canceled', 'closed', 'reversed'], true) => 'bg-slate-100 text-slate-600 ring-slate-500/15',
        default => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset {$classes}"]) }}>
    {{ $status }}
</span>
