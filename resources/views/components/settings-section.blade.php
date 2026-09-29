@props([
    'title',
    'description' => null,
    'danger' => false,
])

<section {{ $attributes->merge(['class' => 'px-6 py-6 sm:px-8']) }}>
    <header class="mb-5 max-w-xl">
        <h2 @class([
            'text-base font-semibold',
            'text-red-700' => $danger,
            'text-slate-900' => ! $danger,
        ])>{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 text-sm leading-relaxed text-slate-500">{{ $description }}</p>
        @endif
    </header>

    {{ $slot }}

    @isset($actions)
        <div class="mt-6 flex flex-wrap items-center gap-4 border-t border-slate-100 pt-5">
            {{ $actions }}
        </div>
    @endisset
</section>
