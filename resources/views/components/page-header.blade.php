@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0 flex-1">
        <h1 class="text-2xl font-bold tracking-tight break-words text-slate-900">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm break-words text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
