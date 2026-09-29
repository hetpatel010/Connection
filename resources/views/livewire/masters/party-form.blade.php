<div data-shortcut-scope="party-form">
    <form wire:submit="save" data-shortcut-form>
        <x-form-card>
            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-form-field :label="__('Name')" for="name">
                        <x-text-input wire:model="name" id="name" class="block w-full" required />
                        <x-slot:error><x-input-error :messages="$errors->get('name')" class="mt-1.5" /></x-slot:error>
                    </x-form-field>
                </div>
                <x-form-field :label="__('Legal name')" for="legalName">
                    <x-text-input wire:model="legalName" id="legalName" class="block w-full" />
                </x-form-field>
                <x-form-field :label="__('Phone')" for="phone">
                    <x-text-input wire:model="phone" id="phone" class="block w-full" />
                </x-form-field>
                <x-form-field :label="__('Email')" for="email">
                    <x-text-input wire:model="email" id="email" type="email" class="block w-full" />
                </x-form-field>
                <x-form-field :label="__('GSTIN')" for="gstin">
                    <x-text-input wire:model="gstin" id="gstin" class="block w-full font-mono" />
                </x-form-field>
                <x-form-field :label="__('PAN')" for="pan">
                    <x-text-input wire:model="pan" id="pan" class="block w-full font-mono" />
                </x-form-field>
                <x-form-field :label="__('City')" for="billingCity">
                    <x-text-input wire:model="billingCity" id="billingCity" class="block w-full" />
                </x-form-field>
                <x-form-field :label="__('State')" for="billingState">
                    <x-text-input wire:model="billingState" id="billingState" class="block w-full" />
                </x-form-field>
                <div class="md:col-span-2">
                    <x-form-field :label="__('Billing address')" for="billingAddressLine1">
                        <x-text-input wire:model="billingAddressLine1" id="billingAddressLine1" class="block w-full" />
                    </x-form-field>
                </div>
                <x-form-field :label="__('Payment terms')" for="paymentTerms">
                    <x-text-input wire:model="paymentTerms" id="paymentTerms" class="block w-full" />
                </x-form-field>
                <x-form-field :label="__('Opening balance (₹)')" for="openingBalance">
                    <x-text-input wire:model="openingBalance" id="openingBalance" type="number" step="0.01" min="0" class="block w-full font-mono" />
                </x-form-field>
                <div class="md:col-span-2">
                    <x-form-field :label="__('Notes')" for="notes">
                        <x-textarea-input wire:model="notes" id="notes" class="block w-full" />
                    </x-form-field>
                </div>
            </div>

            <x-slot:footer>
                <a href="{{ $partyType === 'supplier' ? route('masters.suppliers.index') : route('masters.customers.index') }}" wire:navigate class="text-sm font-medium text-slate-600 transition hover:text-slate-900">{{ __('Cancel') }}</a>
                <x-form-actions :show-shortcut="true">
                    <x-primary-button data-shortcut-save>{{ $party ? __('Update') : __('Save') }}</x-primary-button>
                </x-form-actions>
            </x-slot:footer>
        </x-form-card>
    </form>
</div>
