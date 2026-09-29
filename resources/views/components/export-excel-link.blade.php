@props([
    'module',
    'search' => '',
    'type' => '',
    'group' => '',
    'status' => '',
    'view' => '',
])

@php
    $params = array_filter([
        'search' => $search !== '' ? $search : null,
        'type' => $type !== '' ? $type : null,
        'group' => $group !== '' ? $group : null,
        'status' => $status !== '' ? $status : null,
        'view' => $view !== '' && $view !== 'all' ? $view : null,
    ]);
@endphp

<a href="{{ route('exports.list-excel', ['module' => $module] + $params) }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50']) }}>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
    </svg>
    {{ __('Export Excel') }}
</a>
