@props([
    'placeholder' => __('Search...'),
])

<div class="relative max-w-md flex-1">
    <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
    </svg>
    <input
        type="search"
        placeholder="{{ $placeholder }}"
        data-shortcut-search
        {{ $attributes->merge(['class' => 'form-input w-full pl-10']) }}
    />
</div>
