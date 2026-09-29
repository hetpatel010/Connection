<div>
    <form wire:submit="save" class="mx-auto max-w-2xl space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="expenseDate" :value="__('Expense date')" />
                <x-text-input wire:model="expenseDate" id="expenseDate" type="date" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="expenseAccountId" :value="__('Expense account')" />
                <x-select-input wire:model="expenseAccountId" id="expenseAccountId" class="mt-1 block w-full" required>
                    <option value="">{{ __('Select account') }}</option>
                    @foreach ($expenseAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="paymentAccountId" :value="__('Paid from')" />
                <x-select-input wire:model="paymentAccountId" id="paymentAccountId" class="mt-1 block w-full" required>
                    <option value="">{{ __('Select account') }}</option>
                    @foreach ($paymentAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="partyId" :value="__('Vendor (optional)')" />
                <x-select-input wire:model="partyId" id="partyId" class="mt-1 block w-full">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="amount" :value="__('Amount (₹)')" />
                <x-text-input wire:model="amount" id="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="taxAmount" :value="__('GST (₹)')" />
                <x-text-input wire:model="taxAmount" id="taxAmount" type="number" step="0.01" min="0" class="mt-1 block w-full" />
            </div>
        </div>

        <div>
            <x-input-label for="description" :value="__('Description')" />
            <x-text-input wire:model="description" id="description" type="text" class="mt-1 block w-full" required />
        </div>

        <div class="flex justify-end">
            <x-primary-button>{{ __('Record Expense') }}</x-primary-button>
        </div>
    </form>
</div>
