@php
    $icon = asset_url(branding('product.icon', '/favicon.svg'));
    $initial = strtoupper(substr(product_name(), 0, 1));
@endphp

@if ($icon)
    <img src="{{ $icon }}" alt="{{ product_name() }}" {{ $attributes->merge(['class' => 'h-9 w-9 rounded-lg object-contain']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white']) }}>
        {{ $initial }}
    </div>
@endif
