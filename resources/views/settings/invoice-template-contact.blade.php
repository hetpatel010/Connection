<x-app-layout>
    <x-page-shell>
        <x-page-header
            :title="__('Email SVEO')"
            :description="__('Send a message about a custom invoice template straight from this page.')"
        />

        <x-form-card>
            <x-slot:header>
                <h2 class="text-base font-semibold text-slate-900">{{ __('Compose message') }}</h2>
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('Replies are handled by :vendor during business hours.', ['vendor' => branding('vendor.name')]) }}
                </p>
            </x-slot:header>

            <form method="POST" action="{{ route('settings.invoice-templates.contact.store') }}" class="space-y-5">
                @csrf

                @unless ($mailIsDeliverable)
                    <x-alert type="warning">
                        {{ $mailBlockReason }}
                    </x-alert>
                @endunless

                <x-form-field label="To" for="toEmail" hint="This address is fixed and cannot be changed.">
                    <x-text-input
                        id="toEmail"
                        name="toEmail"
                        type="text"
                        :value="$vendorEmail"
                        readonly
                    />
                </x-form-field>

                <x-form-field label="Subject" for="subject">
                    <x-text-input
                        id="subject"
                        name="subject"
                        type="text"
                        class="mt-1.5 w-full max-w-xl"
                        :value="old('subject', $defaultSubject)"
                        required
                        maxlength="150"
                    />
                    <x-input-error class="mt-2" :messages="$errors->get('subject')" />
                </x-form-field>

                <x-form-field label="Message" for="message" hint="Tell us which layout you need, including any fields, columns, or branding you want.">
                    <x-textarea-input
                        id="message"
                        name="message"
                        rows="6"
                        class="mt-1.5 w-full"
                        placeholder="{{ __('Describe the invoice template you would like us to design.') }}"
                        required
                        maxlength="5000"
                    >{{ old('message') }}</x-textarea-input>
                    <x-input-error class="mt-2" :messages="$errors->get('message')" />
                </x-form-field>

                <div class="flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                    <x-primary-button type="submit">{{ __('Send Email') }}</x-primary-button>

                    <a
                        href="{{ route('settings.invoice-templates') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                    >
                        {{ __('Back to Invoice Templates') }}
                    </a>
                </div>
            </form>
        </x-form-card>
    </x-page-shell>
</x-app-layout>
