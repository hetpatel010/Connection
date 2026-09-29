<div>
    {{-- Product summary --}}
    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-bold tracking-tight text-slate-900">{{ $product->name }}</h2>
                    @if ($product->sku)
                        <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs font-medium text-slate-600">{{ $product->sku }}</span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-slate-500">
                    {{ __('Current stock') }}:
                    <span class="font-mono font-semibold text-slate-900">{{ $currentStock + 0 }}</span>
                    {{ $product->unit?->symbol ?: '' }}
                </p>
            </div>
            <a href="{{ route('inventory.ledger', $product) }}" wire:navigate class="text-sm font-medium text-indigo-600 transition hover:text-indigo-500">{{ __('View Ledger') }}</a>
        </div>
    </div>

    <form wire:submit="save" class="mx-auto max-w-2xl space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="movementDate" :value="__('Date')" />
                <x-text-input wire:model="movementDate" id="movementDate" type="date" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="direction" :value="__('Type')" />
                <x-select-input wire:model="direction" id="direction" class="mt-1 block w-full">
                    <option value="in">{{ __('Stock Adjustment In (+)') }}</option>
                    <option value="out">{{ __('Stock Adjustment Out (−)') }}</option>
                </x-select-input>
            </div>
            <div>
                <x-input-label for="quantity" :value="__('Quantity')" />
                <x-text-input wire:model="quantity" id="quantity" type="number" step="0.0001" min="0.0001" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="reference" :value="__('Reference')" />
                <x-text-input wire:model="reference" id="reference" type="text" maxlength="100" class="mt-1 block w-full" :placeholder="__('e.g. ADJ-0001')" />
            </div>
        </div>

        <div>
            <x-input-label for="notes" :value="__('Reason / Notes')" />
            <x-textarea-input wire:model="notes" id="notes" rows="3" class="mt-1 block w-full" :placeholder="__('Why is the stock being adjusted?')" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
            <p class="text-sm text-slate-500">
                {{ __('Adjusted by') }}: <span class="font-medium text-slate-900">{{ auth()->user()->name }}</span>
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('inventory.ledger', $product) }}" wire:navigate class="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</a>
                <x-primary-button>{{ __('Save Adjustment') }}</x-primary-button>
            </div>
        </div>
    </form>
</div>