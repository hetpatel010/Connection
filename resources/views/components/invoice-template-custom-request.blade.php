<x-card class="overflow-hidden !p-0">
    <div class="grid gap-0 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
        <div class="border-b border-slate-200 p-6 lg:border-b-0 lg:border-r">
            <div class="flex items-start gap-4">
                <a
                    href="{{ branding('vendor.website') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="shrink-0 rounded-xl bg-white p-2 ring-1 ring-slate-200"
                >
                    <img
                        src="{{ branding('vendor.logo_url') }}"
                        alt="{{ branding('vendor.name') }}"
                        class="h-10 w-auto max-w-[7rem] object-contain"
                        loading="lazy"
                    >
                </a>
                <div class="min-w-0">
                    <h3 class="text-base font-semibold text-slate-900">{{ __('Need a custom invoice template?') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('We can design a branded GST or non-GST layout tailored to your business — letterhead style, colours, extra fields, or industry-specific formats.') }}
                    </p>
                </div>
            </div>

            <ul class="mt-5 space-y-2 text-sm text-slate-600">
                <li class="flex gap-2">
                    <span class="mt-0.5 text-indigo-500" aria-hidden="true">•</span>
                    <span>{{ __('Custom header, footer, and terms blocks') }}</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 text-indigo-500" aria-hidden="true">•</span>
                    <span>{{ __('Multi-copy or thermal printer layouts') }}</span>
                </li>
                <li class="flex gap-2">
                    <span class="mt-0.5 text-indigo-500" aria-hidden="true">•</span>
                    <span>{{ __('Domain-specific columns and bilingual invoices') }}</span>
                </li>
            </ul>
        </div>

        <div class="bg-slate-50 p-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('Contact') }}</p>
            <p class="mt-2 text-sm font-semibold text-slate-900">{{ branding('vendor.name') }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ branding('vendor.legal_name') }}</p>

            <dl class="mt-4 space-y-3 text-sm">
                @if (branding('vendor.email'))
                    <div>
                        <dt class="text-xs font-medium text-slate-500">{{ __('Email') }}</dt>
                        <dd class="mt-0.5">
                            <a href="mailto:{{ branding('vendor.email') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                {{ branding('vendor.email') }}
                            </a>
                        </dd>
                    </div>
                @endif

                @if (branding('vendor.phone'))
                    <div>
                        <dt class="text-xs font-medium text-slate-500">{{ __('Phone') }}</dt>
                        <dd class="mt-0.5 font-medium text-slate-700">{{ branding('vendor.phone') }}</dd>
                    </div>
                @endif

                <div>
                    <dt class="text-xs font-medium text-slate-500">{{ __('Website') }}</dt>
                    <dd class="mt-0.5">
                        <a href="{{ branding('vendor.website') }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:text-indigo-500">
                            {{ branding('vendor.website_label') }}
                        </a>
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium text-slate-500">{{ __('Office') }}</dt>
                    <dd class="mt-0.5 text-slate-600">{{ branding('vendor.location') }}</dd>
                </div>
            </dl>

            <div class="mt-5 flex flex-wrap gap-2">
                <a
                    href="{{ branding('vendor.contact_url') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500"
                >
                    {{ __('Request custom template') }}
                </a>
                @if (branding('vendor.email'))
                    <a
                        href="{{ route('settings.invoice-templates.contact') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        {{ __('Email SVEO') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-card>
