<div data-shortcut-scope="account-form">
    <form wire:submit="save" data-shortcut-form>
        <x-form-card>
            <x-slot:header>
                <div class="flex flex-col gap-1">
                    <h2 class="text-base font-semibold text-slate-900">
                        {{ $account ? __('Edit account') : __('Create account') }}
                    </h2>
                    <p class="text-sm text-slate-500">
                        {{ __('Code, name and type are the minimum required for a ledger account.') }}
                    </p>
                </div>
            </x-slot:header>

            <div class="grid gap-5 md:grid-cols-2">
                <x-form-field :label="__('Account code')" for="account-code" :hint="$locked['code'] ? __('The code is locked because this account is in use or is a system account.') : null">
                    <x-text-input
                        wire:model="code"
                        id="account-code"
                        class="block w-full font-mono"
                        :disabled="$locked['code']"
                        maxlength="20"
                        placeholder="1600"
                        required
                    />
                    <x-slot:error><x-input-error :messages="$errors->get('code')" class="mt-1.5" /></x-slot:error>
                </x-form-field>

                <x-form-field :label="__('Account name')" for="account-name">
                    <x-text-input wire:model="name" id="account-name" class="block w-full" maxlength="255" required />
                    <x-slot:error><x-input-error :messages="$errors->get('name')" class="mt-1.5" /></x-slot:error>
                </x-form-field>

                <x-form-field :label="__('Account type')" for="account-type" :hint="$locked['type'] ? __('The type is locked because changing it would corrupt existing accounting records.') : null">
                    <x-select-input wire:model.live="type" id="account-type" :disabled="$locked['type']" required>
                        <option value="">{{ __('Select a type') }}</option>
                        @foreach ($accountTypes as $accountType)
                            <option value="{{ $accountType->value }}">{{ $accountType->label() }}</option>
                        @endforeach
                    </x-select-input>
                    <x-slot:error><x-input-error :messages="$errors->get('type')" class="mt-1.5" /></x-slot:error>
                </x-form-field>

                <x-form-field :label="__('Account group')" for="account-group" :hint="__('Inherited from the parent group when one is selected.')">
                    <x-text-input wire:model="group" id="account-group" class="block w-full" maxlength="255" />
                    <x-slot:error><x-input-error :messages="$errors->get('group')" class="mt-1.5" /></x-slot:error>
                </x-form-field>

                <div class="md:col-span-2">
                    <x-form-field :label="__('Parent account')" for="account-parent" :hint="__('Leave empty to create a group header. Group headers cannot receive journal entries.')">
                        <x-select-input wire:model.live="parentId" id="account-parent">
                            <option value="">{{ __('None — this is a group header') }}</option>
                            @foreach ($parentOptions as $parentOption)
                                <option value="{{ $parentOption->id }}">{{ $parentOption->code }} — {{ $parentOption->name }}</option>
                            @endforeach
                        </x-select-input>
                        <x-slot:error><x-input-error :messages="$errors->get('parentId')" class="mt-1.5" /></x-slot:error>
                    </x-form-field>
                </div>

                <x-form-field :label="__('Opening balance (₹)')" for="account-opening-balance" :hint="$locked['opening_balance'] ? __('The opening balance is locked because opening balances have already been posted to the ledger.') : null">
                    <x-text-input
                        wire:model="openingBalance"
                        id="account-opening-balance"
                        type="number"
                        step="0.01"
                        min="0"
                        class="block w-full font-mono"
                        :disabled="$locked['opening_balance']"
                    />
                    <x-slot:error><x-input-error :messages="$errors->get('openingBalance')" class="mt-1.5" /></x-slot:error>
                </x-form-field>

                <x-form-field :label="__('Opening balance side')" for="account-opening-side">
                    <x-select-input wire:model="openingBalanceSide" id="account-opening-side" :disabled="$locked['opening_balance']">
                        <option value="">{{ __('Use the normal balance side for this type') }}</option>
                        @foreach ($balanceSides as $balanceSide)
                            <option value="{{ $balanceSide->value }}">{{ ucfirst($balanceSide->value) }}</option>
                        @endforeach
                    </x-select-input>
                    <x-slot:error><x-input-error :messages="$errors->get('openingBalanceSide')" class="mt-1.5" /></x-slot:error>
                </x-form-field>

                <x-form-field :label="__('Status')" for="account-status">
                    <x-select-input wire:model="status" id="account-status" required>
                        @foreach ($accountStatuses as $accountStatus)
                            <option value="{{ $accountStatus->value }}">{{ ucfirst($accountStatus->value) }}</option>
                        @endforeach
                    </x-select-input>
                    <x-slot:error><x-input-error :messages="$errors->get('status')" class="mt-1.5" /></x-slot:error>
                </x-form-field>

                <div class="md:col-span-2">
                    <x-form-field :label="__('Description / notes')" for="account-description">
                        <x-textarea-input wire:model="description" id="account-description" class="block w-full" :rows="3" />
                        <x-slot:error><x-input-error :messages="$errors->get('description')" class="mt-1.5" /></x-slot:error>
                    </x-form-field>
                </div>
            </div>

            <x-slot:footer>
                <a href="{{ route('accounting.accounts.index') }}" wire:navigate class="text-sm font-medium text-slate-600 transition hover:text-slate-900">{{ __('Cancel') }}</a>
                <x-form-actions>
                    <x-primary-button data-shortcut-save>{{ $account ? __('Update account') : __('Save account') }}</x-primary-button>
                </x-form-actions>
            </x-slot:footer>
        </x-form-card>
    </form>
</div>
