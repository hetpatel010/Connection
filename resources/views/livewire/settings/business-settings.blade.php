<div data-shortcut-scope="business-settings">
    <form wire:submit="save" data-shortcut-form class="mx-auto max-w-3xl space-y-6">
        <x-card flush>
            <x-slot:header>
                <h3 class="font-semibold text-slate-900">{{ __('Business profile') }}</h3>
            </x-slot:header>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="name" :value="__('Display name')" />
                    <x-text-input wire:model="name" id="name" type="text" class="mt-1.5 block w-full" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="legalName" :value="__('Legal name')" />
                    <x-text-input wire:model="legalName" id="legalName" type="text" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input wire:model="email" id="email" type="email" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label for="phone" :value="__('Phone')" />
                    <x-text-input wire:model="phone" id="phone" type="text" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label for="gstin" :value="__('GSTIN')" />
                    <x-text-input wire:model="gstin" id="gstin" type="text" maxlength="15" class="mt-1.5 block w-full font-mono uppercase" />
                </div>
                <div>
                    <x-input-label for="pan" :value="__('PAN')" />
                    <x-text-input wire:model="pan" id="pan" type="text" maxlength="10" class="mt-1.5 block w-full font-mono uppercase" />
                </div>
            </div>
        </x-card>

        <x-card flush>
            <x-slot:header>
                <h3 class="font-semibold text-slate-900">{{ __('Address') }}</h3>
            </x-slot:header>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="addressLine1" :value="__('Address line 1')" />
                    <x-text-input wire:model="addressLine1" id="addressLine1" type="text" class="mt-1.5 block w-full" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="addressLine2" :value="__('Address line 2')" />
                    <x-text-input wire:model="addressLine2" id="addressLine2" type="text" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label for="city" :value="__('City')" />
                    <x-text-input wire:model="city" id="city" type="text" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label for="state" :value="__('State')" />
                    <x-text-input wire:model="state" id="state" type="text" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label for="postalCode" :value="__('Postal code')" />
                    <x-text-input wire:model="postalCode" id="postalCode" type="text" class="mt-1.5 block w-full" />
                </div>
                <div>
                    <x-input-label for="timezone" :value="__('Timezone')" />
                    <x-text-input wire:model="timezone" id="timezone" type="text" class="mt-1.5 block w-full" required />
                </div>
            </div>
        </x-card>

        <x-card flush>
            <x-slot:header>
                <h3 class="font-semibold text-slate-900">{{ __('Company logo & invoice branding') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Shown on printed invoices and bills — like Vyapar firm settings.') }}</p>
            </x-slot:header>

            <div class="space-y-5 p-5">
                <div>
                    <x-input-label for="logo" :value="__('Company logo')" />
                    <div class="mt-2 flex flex-wrap items-start gap-4">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="" class="h-20 w-auto max-w-full rounded-lg border border-slate-200 bg-white p-2 object-contain">
                        @elseif ($existingLogoUrl)
                            <img src="{{ $existingLogoUrl }}" alt="" class="h-20 w-auto max-w-full rounded-lg border border-slate-200 bg-white p-2 object-contain">
                        @else
                            <div class="flex h-20 w-32 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 text-xs text-slate-400">
                                {{ __('No logo') }}
                            </div>
                        @endif
                        <div class="space-y-2">
                            <input wire:model="logo" id="logo" type="file" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100" />
                            <p class="text-xs text-slate-500">{{ __('PNG, JPG or WebP. Max 2 MB. Recommended ~300×100 px.') }}</p>
                            @if ($existingLogoUrl)
                                <button type="button" wire:click="removeLogo" wire:confirm="{{ __('Remove the company logo?') }}" class="text-sm font-medium text-red-600 hover:text-red-700">
                                    {{ __('Remove logo') }}
                                </button>
                            @endif
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="website" :value="__('Website (optional)')" />
                    <x-text-input wire:model="website" id="website" type="text" class="mt-1.5 block w-full" placeholder="https://example.com" />
                    <x-input-error :messages="$errors->get('website')" class="mt-2" />
                </div>
            </div>
        </x-card>

        <x-card flush>
            <x-slot:header>
                <h3 class="font-semibold text-slate-900">{{ __('Bank & payment details') }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ __('Printed on invoices so customers know where to pay.') }}</p>
            </x-slot:header>

            <div class="grid gap-5 p-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-input-label for="bankName" :value="__('Bank name')" />
                    <x-text-input wire:model="bankName" id="bankName" type="text" class="mt-1.5 block w-full" placeholder="{{ __('e.g. State Bank of India') }}" />
                </div>
                <div>
                    <x-input-label for="bankAccount" :value="__('Account number')" />
                    <x-text-input wire:model="bankAccount" id="bankAccount" type="text" class="mt-1.5 block w-full font-mono" />
                </div>
                <div>
                    <x-input-label for="bankIfsc" :value="__('IFSC code')" />
                    <x-text-input wire:model="bankIfsc" id="bankIfsc" type="text" class="mt-1.5 block w-full font-mono uppercase" maxlength="20" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="upiId" :value="__('UPI ID')" />
                    <x-text-input wire:model="upiId" id="upiId" type="text" class="mt-1.5 block w-full font-mono" placeholder="name@upi" />
                </div>
            </div>
        </x-card>

        <x-card flush>
            <x-slot:header>
                <h3 class="font-semibold text-slate-900">{{ __('Terms, footer & signature') }}</h3>
            </x-slot:header>

            <div class="space-y-5 p-5">
                <div>
                    <x-input-label for="termsAndConditions" :value="__('Terms & conditions')" />
                    <x-textarea-input wire:model="termsAndConditions" id="termsAndConditions" rows="5" class="mt-1.5 block w-full" placeholder="{{ __('Goods once sold will not be taken back. Subject to local jurisdiction...') }}" />
                    <x-input-error :messages="$errors->get('termsAndConditions')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="invoiceFooter" :value="__('Invoice footer note')" />
                    <x-textarea-input wire:model="invoiceFooter" id="invoiceFooter" rows="3" class="mt-1.5 block w-full" placeholder="{{ __('Thank you for your business!') }}" />
                    <x-input-error :messages="$errors->get('invoiceFooter')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="signatureLabel" :value="__('Signature label')" />
                    <x-text-input wire:model="signatureLabel" id="signatureLabel" type="text" class="mt-1.5 block w-full max-w-sm" placeholder="{{ __('Authorized Signatory') }}" />
                    <p class="mt-1 text-xs text-slate-500">{{ __('Leave blank to use “Authorized Signatory” on invoices.') }}</p>
                </div>
            </div>
        </x-card>

        <x-form-actions class="justify-end">
            <x-primary-button data-shortcut-save>{{ __('Save settings') }}</x-primary-button>
        </x-form-actions>

        @can('exportData', business_context()->business())
            <x-card flush class="mt-6">
                <x-slot:header>
                    <h3 class="font-semibold text-slate-900">{{ __('Data export') }}</h3>
                </x-slot:header>
                <div class="p-5">
                    <p class="text-sm text-slate-600">{{ __('Export sales, purchases, parties, products, and more as Excel or JSON backup for the active financial year.') }}</p>
                    <a href="{{ route('settings.export.index') }}"
                        class="mt-4 inline-flex items-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        {{ __('Open export options') }}
                    </a>
                </div>
            </x-card>
        @endcan
    </form>
</div>
