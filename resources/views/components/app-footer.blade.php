<footer {{ $attributes->merge(['class' => 'shrink-0 border-t border-slate-200/60 bg-white/80 backdrop-blur-sm']) }}>
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-3.5 text-sm text-slate-500 sm:justify-start sm:px-6 lg:px-8">
        <span class="font-medium text-slate-700">{{ branding('product.name', config('app.name')) }}</span>
        @auth
            <span aria-hidden="true" class="text-slate-300">·</span>
            <a href="{{ route('about') }}" wire:navigate class="transition hover:text-indigo-600">{{ __('About & support') }}</a>
            <span aria-hidden="true" class="text-slate-300">·</span>
            <a href="{{ route('profile') }}" wire:navigate class="transition hover:text-indigo-600">{{ __('Profile') }}</a>
        @endauth
        <span aria-hidden="true" class="hidden text-slate-300 sm:inline">·</span>
        <span class="hidden sm:inline">{{ branding('vendor.location') }}</span>
    </div>
</footer>
