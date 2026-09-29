<div>
    @php
        $isNonGst = ($billingType ?? 'gst') === 'non_gst';
    @endphp

    @if ($editing && $documentNumber)
        <p class="mb-4 text-sm text-slate-600">
            {{ __('Editing draft') }} <span class="font-mono font-semibold text-slate-900">{{ $documentNumber }}</span>
        </p>
    @endif

    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="billingType" :value="__('Billing type')" />
                <x-select-input wire:model.live="billingType" id="billingType" class="mt-1 block w-full">
                    <option value="gst">{{ __('GST Purchase Bill') }}</option>
                    <option value="non_gst">{{ __('Non-GST Bill') }}</option>
                </x-select-input>
            </div>
            <div>
                <x-input-label for="documentTemplate" :value="__('Print template')" />
                <x-select-input wire:model="documentTemplate" id="documentTemplate" class="mt-1 block w-full">
                    <option value="">{{ __('Business default') }}</option>
                    @foreach ($templateOptions as $template)
                        <option value="{{ $template['slug'] }}">{{ $template['name'] }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="billDate" :value="__('Bill date')" />
                <x-text-input wire:model="billDate" id="billDate" type="date" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('billDate')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="partyId" :value="__('Supplier')" />
                <x-select-input wire:model="partyId" id="partyId" class="mt-1 block w-full" required>
                    <option value="">{{ __('Select supplier') }}</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error :messages="$errors->get('partyId')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="settlementMode" :value="__('Settlement')" />
                <x-select-input wire:model.live="settlementMode" id="settlementMode" class="mt-1 block w-full">
                    <option value="credit">{{ __('Credit (Accounts Payable)') }}</option>
                    <option value="cash">{{ __('Cash / Bank') }}</option>
                </x-select-input>
            </div>
            @if ($settlementMode === 'cash')
                <div>
                    <x-input-label for="paymentAccountId" :value="__('Payment account')" />
                    <x-select-input wire:model="paymentAccountId" id="paymentAccountId" class="mt-1 block w-full">
                        <option value="">{{ __('Select account') }}</option>
                        @foreach ($paymentAccounts as $account)
                            <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('paymentAccountId')" class="mt-2" />
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Product') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Description') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Qty') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Rate (₹)') }}</th>
                        @unless ($isNonGst)
                            <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Tax %') }}</th>
                        @endunless
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($lines as $index => $line)
                        <tr wire:key="bill-line-{{ $index }}">
                            <td class="px-4 py-3">
                                <x-select-input wire:model.live="lines.{{ $index }}.product_id" class="w-full">
                                    <option value="">{{ __('Manual') }}</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                                    @endforeach
                                </x-select-input>
                            </td>
                            <td class="px-4 py-3">
                                <x-text-input wire:model="lines.{{ $index }}.description" type="text" class="w-full" required />
                            </td>
                            <td class="px-4 py-3">
                                <x-text-input wire:model="lines.{{ $index }}.quantity" type="number" step="0.0001" min="0.0001" class="w-full text-right" required />
                            </td>
                            <td class="px-4 py-3">
                                <x-text-input wire:model="lines.{{ $index }}.unit_price" type="number" step="0.01" min="0" class="w-full text-right" required />
                            </td>
                            @unless ($isNonGst)
                                <td class="px-4 py-3">
                                    <x-text-input wire:model="lines.{{ $index }}.tax_rate" type="number" step="0.01" min="0" class="w-full text-right" />
                                </td>
                            @endunless
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="removeLine({{ $index }})" class="text-sm text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <button type="button" wire:click="addLine" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('Add line') }}</button>
            <div class="flex flex-wrap gap-2">
                @if ($editing)
                    @can('delete', $bill)
                        <button type="button" wire:click="deleteDraft" wire:confirm="{{ __('Delete this draft bill?') }}"
                            class="inline-flex items-center rounded-md border border-red-300 bg-white px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">
                            {{ __('Delete Draft') }}
                        </button>
                    @endcan
                @endif
                <button type="button" wire:click="saveDraft"
                    class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    {{ $editing ? __('Save Draft') : __('Save as Draft') }}
                </button>
                @if ($editing)
                    @can('confirm', $bill)
                        <button type="button" wire:click="confirm"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            {{ __('Confirm Bill') }}
                        </button>
                    @endcan
                @else
                    <button type="button" wire:click="confirm"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        {{ __('Save & Confirm') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
