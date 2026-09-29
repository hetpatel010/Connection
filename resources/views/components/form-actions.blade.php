<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-3']) }}>
    {{ $slot }}

    @if ($showShortcut ?? true)
        <x-kbd-shortcut :action="$shortcut ?? 'save'" />
    @endif
</div>
