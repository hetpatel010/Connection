<div class="flex items-center gap-2">
    @if ($businesses->isNotEmpty())
        <label for="business-switcher" class="sr-only">{{ __('Shop / brand') }}</label>
        <x-select-input id="business-switcher" wire:model.live="selectedBusinessId" class="min-w-[10rem]">
            @foreach ($businesses as $business)
                <option value="{{ $business->id }}">{{ $business->name }}</option>
            @endforeach
        </x-select-input>
    @endif

    @can('create', \App\Models\Business::class)
        <a href="{{ route('businesses.create') }}" wire:navigate
            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-600 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600"
            title="{{ __('Add shop or brand') }}">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span class="sr-only">{{ __('Add shop or brand') }}</span>
        </a>
    @endcan
</div>
