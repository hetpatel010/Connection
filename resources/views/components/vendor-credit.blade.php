@props([
    'compact' => false,
    'theme' => 'light',
])

@php
    $vendorName = branding('vendor.name');
    $website = branding('vendor.website');
    $websiteLabel = branding('vendor.website_label');
    $credit = str_replace(':vendor', $vendorName, branding('credit_line', 'Designed & developed by :vendor'));
@endphp

@if ($compact)
    <p {{ $attributes->merge(['class' => 'text-sm leading-relaxed '.($theme === 'dark' ? 'text-slate-400' : 'text-slate-500')]) }}>
        {{ $credit }}
        <a href="{{ $website }}" target="_blank" rel="noopener noreferrer" class="mt-0.5 block font-medium transition hover:underline {{ $theme === 'dark' ? 'text-indigo-300 hover:text-indigo-200' : 'text-indigo-600 hover:text-indigo-500' }}">
            {{ $websiteLabel }}
        </a>
    </p>
@else
    <div {{ $attributes->merge(['class' => 'flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between sm:gap-4']) }}>
        <p class="text-sm text-slate-500">
            {{ $credit }}
        </p>
        <a href="{{ $website }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 transition hover:text-indigo-500">
            {{ $websiteLabel }}
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
        </a>
    </div>
@endif
