@props([
    'flush' => false,
])

<div {{ $attributes->merge(['class' => 'surface-card']) }}>
    @isset($header)
        <div class="border-b border-slate-100 px-5 py-5 sm:px-6 sm:py-6">
            {{ $header }}
        </div>
    @endisset

    <div @class(['p-5' => ! $flush])>
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-slate-100 bg-slate-50/40 px-5 py-4">
            {{ $footer }}
        </div>
    @endisset
</div>
