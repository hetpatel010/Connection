@props([
    'maxWidth' => '3xl',
])

@php
    $widthClass = match ($maxWidth) {
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        default => 'max-w-3xl',
    };
@endphp

<div {{ $attributes->merge(['class' => "mx-auto {$widthClass} surface-card"]) }}>
    @isset($header)
        <div class="border-b border-slate-100 px-6 py-4">
            {{ $header }}
        </div>
    @endisset

    <div class="p-6">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/40 px-6 py-4">
            {{ $footer }}
        </div>
    @endisset
</div>
