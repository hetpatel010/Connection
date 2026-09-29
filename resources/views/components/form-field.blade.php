@props([
    'label',
    'for' => null,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    <x-input-label :for="$for" :value="$label" />
    @if ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    {{ $slot }}
    @isset($error)
        {{ $error }}
    @endisset
</div>
