@props([
    'type' => 'success',
])

@php
    $classes = match ($type) {
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'danger' => 'border-red-200 bg-red-50 text-red-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        'info' => 'border-indigo-200 bg-indigo-50 text-indigo-800',
        default => 'border-slate-200 bg-slate-50 text-slate-800',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-xl border px-4 py-3 text-sm {$classes}"]) }} role="alert">
    {{ $slot }}
</div>
