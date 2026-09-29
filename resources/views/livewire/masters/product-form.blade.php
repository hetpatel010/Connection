<div data-shortcut-scope="product-form">
    <form wire:submit="save" data-shortcut-form>
        <x-form-card max-width="4xl">
            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <x-form-field :label="__('Product name')" for="name">
                        <x-text-input wire:model="name" id="name" class="block w-full" required />
                        <x-slot:error><x-input-error :messages="$errors->get('name')" class="mt-1.5" /></x-slot:error>
                    </x-form-field>
                </div>
                <x-form-field :label="__('SKU')" for="sku">
                    <x-text-input wire:model="sku" id="sku" class="block w-full font-mono" />
                </x-form-field>
                <x-form-field :label="__('Barcode')" for="barcode">
                    <x-text-input wire:model="barcode" id="barcode" class="block w-full" />
                </x-form-field>
                <x-form-field :label="__('HSN/SAC')" for="hsnSac">
                    <x-text-input wire:model="hsnSac" id="hsnSac" class="block w-full font-mono" />
                </x-form-field>
                <x-form-field :label="__('Brand')" for="brand">
                    <x-text-input wire:model="brand" id="brand" class="block w-full" />
                </x-form-field>
                <x-form-field :label="__('Category')" for="categoryId">
                    <x-select-input wire:model="categoryId" id="categoryId" class="w-full">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </x-select-input>
                </x-form-field>
                <x-form-field :label="__('Unit')" for="unitId">
                    <x-select-input wire:model="unitId" id="unitId" class="w-full" required>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                        @endforeach
                    </x-select-input>
                </x-form-field>
                <x-form-field :label="__('Purchase price (₹)')" for="purchasePrice">
                    <x-text-input wire:model="purchasePrice" id="purchasePrice" type="number" step="0.01" min="0" class="block w-full font-mono" />
                </x-form-field>
                <x-form-field :label="__('Selling price (₹)')" for="sellingPrice" :hint="__('Loaded as the invoice rate whenever this product is picked on an invoice.')">
                    <x-text-input wire:model="sellingPrice" id="sellingPrice" type="number" step="0.01" min="0.01" class="block w-full font-mono" required />
                    <x-slot:error><x-input-error :messages="$errors->get('sellingPrice')" class="mt-1.5" /></x-slot:error>
                </x-form-field>
                <x-form-field :label="__('GST rate (%)')" for="taxRate" :hint="__('Applied automatically to any invoice line that uses this product.')">
                    <x-select-input wire:model="taxRate" id="taxRate" class="w-full" required>
                        @foreach ($gstRates as $gstRate)
                            <option value="{{ $gstRate['value'] }}">{{ $gstRate['label'] }}</option>
                        @endforeach
                    </x-select-input>
                    <x-slot:error><x-input-error :messages="$errors->get('taxRate')" class="mt-1.5" /></x-slot:error>
                </x-form-field>
                <x-form-field :label="__('Minimum stock alert')" for="minimumStock" :hint="__('Leave 0 to disable low-stock alerts for this product.')">
                    <x-text-input wire:model="minimumStock" id="minimumStock" type="number" step="0.001" min="0" class="block w-full font-mono" />
                    <x-slot:error><x-input-error :messages="$errors->get('minimumStock')" class="mt-1.5" /></x-slot:error>
                </x-form-field>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input wire:model="taxInclusive" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20" />
                        {{ __('Tax inclusive pricing') }}
                    </label>
                </div>
                <div class="md:col-span-2">
                    <x-form-field :label="__('Description')" for="description">
                        <x-textarea-input wire:model="description" id="description" class="block w-full" />
                    </x-form-field>
                </div>
            </div>

            <x-slot:footer>
                <a href="{{ route('masters.products.index') }}" wire:navigate class="text-sm font-medium text-slate-600 transition hover:text-slate-900">{{ __('Cancel') }}</a>
                <x-form-actions>
                    <x-primary-button data-shortcut-save>{{ $product ? __('Update') : __('Save') }}</x-primary-button>
                </x-form-actions>
            </x-slot:footer>
        </x-form-card>
    </form>
</div>
