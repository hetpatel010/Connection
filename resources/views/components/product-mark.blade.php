@props([
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => ['box' => 'h-9 w-9 text-xs', 'name' => 'text-base'],
        'md' => ['box' => 'h-11 w-11 text-sm', 'name' => 'text-lg'],
        'lg' => ['box' => 'h-12 w-12 text-sm', 'name' => 'text-xl'],
    ];

    $variant = $sizes[$size] ?? $sizes['md'];
    $icon = asset_url(branding('product.icon', '/favicon.svg'));
    $initial = strtoupper(substr(product_name(), 0, 1));
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 min-w-0']) }}>
    @if ($icon)
        <img
            src="{{ $icon }}"
            alt=""
            class="{{ $variant['box'] }} shrink-0 rounded-xl bg-white object-contain p-1.5 shadow-lg shadow-indigo-600/20 ring-1 ring-indigo-100"
        >
    @else
        <div class="{{ $variant['box'] }} flex shrink-0 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white shadow-lg shadow-indigo-600/25">
            {{ $initial }}
        </div>
    @endif
    <div class="min-w-0">
        <p class="{{ $variant['name'] }} truncate font-bold tracking-tight text-slate-900">{{ product_name() }}</p>
        @if (branding('product.tagline'))
            <p class="truncate text-xs text-slate-500">{{ branding('product.tagline') }}</p>
        @endif
    </div>
</div>
