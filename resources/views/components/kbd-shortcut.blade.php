@props([
    'action' => 'save',
])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-slate-400']) }} aria-hidden="true">
    @if ($action === 'save')
        <kbd class="kbd kbd-mac">⌘S</kbd>
        <kbd class="kbd kbd-win">Ctrl+S</kbd>
    @elseif ($action === 'search')
        <kbd class="kbd kbd-mac">⌘K</kbd>
        <kbd class="kbd kbd-win">Ctrl+K</kbd>
    @elseif ($action === 'submit')
        <kbd class="kbd kbd-mac">⌘↵</kbd>
        <kbd class="kbd kbd-win">Ctrl+↵</kbd>
    @elseif ($action === 'help')
        <kbd class="kbd">?</kbd>
    @endif
</span>
