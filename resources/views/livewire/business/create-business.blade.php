<div class="mx-auto max-w-lg">
    <x-card>
        <form wire:submit="save" class="space-y-5 p-6">
            <div>
                <x-input-label for="name" :value="__('Shop / brand name')" />
                <x-text-input wire:model="name" id="name" type="text" class="mt-1.5 block w-full" required autofocus placeholder="{{ __('e.g. Acme Electronics, City Branch') }}" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="state" :value="__('State (place of supply)')" />
                <x-text-input wire:model="state" id="state" type="text" class="mt-1.5 block w-full" required />
                <x-input-error :messages="$errors->get('state')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="gstin" :value="__('GSTIN (optional)')" />
                <x-text-input wire:model="gstin" id="gstin" type="text" maxlength="15" class="mt-1.5 block w-full font-mono uppercase" />
                <x-input-error :messages="$errors->get('gstin')" class="mt-2" />
            </div>

            <p class="text-xs text-slate-500">{{ __('A separate chart of accounts, financial year, and document numbering will be set up automatically.') }}</p>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" wire:navigate class="inline-flex items-center justify-center text-sm font-medium text-slate-600 hover:text-slate-900">
                    {{ __('Cancel') }}
                </a>
                <x-primary-button type="submit">{{ __('Create shop') }}</x-primary-button>
            </div>
        </form>
    </x-card>
</div>
