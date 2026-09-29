<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('About & support')"
            :description="__('Application information, platform details, and vendor contact.')"
        />

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-card>
                    <x-slot:header>
                        <h2 class="font-semibold text-slate-900">{{ branding('product.name') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ branding('product.tagline') }}</p>
                    </x-slot:header>

                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Version') }}</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-700">
                                {{ branding('product.name') }} V{{ branding('product.version') ?: app()->version() }}
                            </dd>
                        </div>
                        @if (business_context()->business())
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Active business') }}</dt>
                                <dd class="mt-1 text-sm text-slate-700">{{ business_context()->business()->name }}</dd>
                            </div>
                        @endif
                        @if (business_context()->financialYear())
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Financial year') }}</dt>
                                <dd class="mt-1 text-sm text-slate-700">FY {{ business_context()->financialYear()->label }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Signed in as') }}</dt>
                            <dd class="mt-1 text-sm text-slate-700">{{ auth()->user()->email }}</dd>
                        </div>
                    </dl>
                </x-card>

                <x-card>
                    <x-slot:header>
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                            <a
                                href="{{ branding('vendor.website') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-white px-6 py-4 ring-1 ring-slate-200/80 transition hover:bg-slate-50 sm:w-auto sm:justify-start sm:px-8 sm:py-5"
                            >
                                <img
                                    src="{{ branding('vendor.logo_url') }}"
                                    alt="{{ branding('vendor.name') }}"
                                    width="360"
                                    height="72"
                                    class="block h-14 w-full max-w-[320px] object-contain object-center sm:h-16 sm:max-w-[360px] sm:object-left"
                                    loading="lazy"
                                />
                            </a>
                            <div class="min-w-0 sm:max-w-xs lg:max-w-sm sm:text-right">
                                <h2 class="text-lg font-semibold text-slate-900">{{ branding('vendor.name') }}</h2>
                                <p class="mt-1.5 text-sm leading-relaxed text-slate-500">{{ branding('vendor.tagline') }}</p>
                            </div>
                        </div>
                    </x-slot:header>

                    <div class="space-y-5">
                        <p class="text-sm leading-relaxed text-slate-600">
                            {{ branding('vendor.description') }}
                        </p>

                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Legal entity') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ branding('vendor.legal_name') }}</p>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Office') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ branding('vendor.address') }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ branding('vendor.location') }}</p>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">{{ __('Capabilities') }}</h3>
                            <ul class="mt-2 flex flex-wrap gap-2">
                                @foreach (branding('vendor.services', []) as $service)
                                    <li class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ $service }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </x-card>
            </div>

            <div class="space-y-6">
                <x-card>
                    <h3 class="font-semibold text-slate-900">{{ __('Quick links') }}</h3>
                    <div class="mt-4 grid gap-2">
                        <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            {{ __('Profile settings') }}
                        </a>
                        @can('manage_settings')
                            <a href="{{ route('settings.business') }}" wire:navigate class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                {{ __('Business settings') }}
                            </a>
                        @endcan
                        <a href="{{ branding('vendor.website') }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50">
                            {{ branding('vendor.website_label') }}
                        </a>
                        <a href="{{ branding('vendor.contact_url') }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50">
                            {{ __('Contact SVEO') }}
                        </a>
                    </div>
                </x-card>

                <x-card class="bg-gradient-to-br from-indigo-600 to-violet-700 !ring-0">
                    <p class="text-sm font-medium text-indigo-100">{{ __('Need help or a custom feature?') }}</p>
                    <p class="mt-2 text-sm text-indigo-100/90">{{ __('Reach out to Sveo Technologies for support, integrations, or product enhancements.') }}</p>
                    <a href="{{ branding('vendor.contact_url') }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50">
                        {{ __('Get in touch') }}
                    </a>
                </x-card>

                <x-vendor-credit />
            </div>
        </div>
    </x-page-shell>
</x-app-layout>
