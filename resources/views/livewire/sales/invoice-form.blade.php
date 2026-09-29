@php
    $isNonGst = ($billingType ?? 'gst') === 'non_gst';
@endphp

<div class="mx-auto max-w-5xl" data-shortcut-scope="invoice-form">
    @if ($editing && $documentNumber)
        <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/15">
            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
            {{ __('Editing draft') }}
            <span class="font-mono">{{ $documentNumber }}</span>
        </div>
    @endif

    <x-card flush>
        {{-- Invoice header fields --}}
        <div class="document-form-section">
            <h3 class="text-sm font-semibold text-slate-900">{{ __('Invoice details') }}</h3>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('Customer, date, and payment terms for this invoice.') }}</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <x-input-label for="billingType" :value="__('Billing type')" />
                    <x-select-input wire:model.live="billingType" id="billingType" class="mt-1.5 w-full">
                        <option value="gst">{{ __('GST Tax Invoice') }}</option>
                        <option value="non_gst">{{ __('Non-GST Bill') }}</option>
                    </x-select-input>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $isNonGst ? __('No tax will be applied. Excluded from GSTR-1.') : __('Standard GST invoice with tax on line items.') }}
                    </p>
                </div>
                <div>
                    <x-input-label for="documentTemplate" :value="__('Print template')" />
                    <x-select-input wire:model="documentTemplate" id="documentTemplate" class="mt-1.5 w-full">
                        <option value="">{{ __('Business default') }}</option>
                        @foreach ($templateOptions as $template)
                            <option value="{{ $template['slug'] }}">{{ $template['name'] }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="invoiceDate" :value="__('Invoice date')" />
                    <x-text-input wire:model="invoiceDate" id="invoiceDate" type="date" class="mt-1.5 block w-full" required />
                    <x-input-error :messages="$errors->get('invoiceDate')" class="mt-2" />
                </div>
                <div class="sm:col-span-2 lg:col-span-2">
                    <x-input-label for="partyId" :value="__('Customer')" />
                    <x-select-input wire:model="partyId" id="partyId" class="mt-1.5 w-full" required>
                        <option value="">{{ __('Select customer') }}</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('partyId')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="settlementMode" :value="__('Settlement')" />
                    <x-select-input wire:model.live="settlementMode" id="settlementMode" class="mt-1.5 w-full">
                        <option value="credit">{{ __('Credit (Accounts Receivable)') }}</option>
                        <option value="cash">{{ __('Cash / Bank') }}</option>
                    </x-select-input>
                </div>
                @if ($settlementMode === 'cash')
                    <div class="sm:col-span-2">
                        <x-input-label for="paymentAccountId" :value="__('Payment account')" />
                        <x-select-input wire:model="paymentAccountId" id="paymentAccountId" class="mt-1.5 w-full">
                            <option value="">{{ __('Select account') }}</option>
                            @foreach ($paymentAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('paymentAccountId')" class="mt-2" />
                    </div>
                @endif
            </div>
        </div>

        {{-- Line items --}}
        <div class="document-form-section !border-b-0">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Line items') }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500">{{ __('Add products or manual lines with quantity, rate, and tax.') }}</p>
                </div>
                <button type="button" wire:click="addLine" class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 transition hover:text-indigo-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    {{ __('Add line') }}
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl ring-1 ring-slate-900/[0.04]">
                <table class="w-full min-w-[50rem] table-fixed divide-y divide-slate-100 text-sm">
                    <colgroup>
                        <col style="width: 16%" />
                        <col />
                        <col style="width: 5.75rem" />
                        <col style="width: 7.5rem" />
                        <col style="width: 6.75rem" />
                        @unless ($isNonGst)
                            <col style="width: 5.25rem" />
                        @endunless
                        <col style="width: 2.75rem" />
                    </colgroup>
                    <thead class="table-head">
                        <tr>
                            <th class="table-head-cell">{{ __('Product') }}</th>
                            <th class="table-head-cell">{{ __('Description') }}</th>
                            <th class="table-head-cell text-right">{{ __('Qty') }}</th>
                            <th class="table-head-cell">{{ __('Unit') }}</th>
                            <th class="table-head-cell text-right">{{ __('Rate (₹)') }}</th>
                            @unless ($isNonGst)
                                <th class="table-head-cell text-right">{{ __('GST %') }}</th>
                            @endunless
                            <th class="table-head-cell"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($lines as $index => $line)
                            <tr wire:key="invoice-line-{{ $index }}" class="table-row align-top">
                                <td class="table-cell !px-3 !py-2.5">
                                    <select wire:model.live="lines.{{ $index }}.product_id" class="form-select-compact w-full">
                                        <option value="">{{ __('Manual') }}</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="table-cell !px-3 !py-2.5">
                                    <input wire:model="lines.{{ $index }}.description" type="text" class="form-input-compact w-full" required />
                                </td>
                                <td class="table-cell !px-2 !py-2.5">
                                    <input wire:model="lines.{{ $index }}.quantity" type="number" step="0.0001" min="0.0001" class="form-input-compact w-full min-w-[4.5rem] text-right tabular-nums [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" required />
                                </td>
                                <td class="table-cell !px-2 !py-2.5">
                                    <select wire:model="lines.{{ $index }}.unit_id" class="form-select-compact w-full">
                                        <option value="">{{ __('—') }}</option>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->id }}">{{ $unit->symbol }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="table-cell !px-2 !py-2.5">
                                    <input wire:model="lines.{{ $index }}.unit_price" type="number" step="0.01" min="0" class="form-input-compact w-full min-w-[5rem] text-right font-mono tabular-nums [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" required />
                                    @if (($productsById->get($line['product_id'] ?? '') ?? null)?->selling_price === 0)
                                        <p class="mt-1 text-[11px] leading-tight text-amber-700">{{ __('This product has no selling price saved — set its rate manually.') }}</p>
                                    @endif
                                </td>
                                @unless ($isNonGst)
                                    <td class="table-cell !px-2 !py-2.5">
                                        <input wire:model="lines.{{ $index }}.tax_rate" type="number" step="0.01" min="0" class="form-input-compact w-full min-w-[4rem] text-right tabular-nums [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                    </td>
                                @endunless
                                <td class="table-cell !px-2 !py-2.5 text-right">
                                    @if (count($lines) > 1)
                                        <button type="button" wire:click="removeLine({{ $index }})" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-red-50 hover:text-red-600" title="{{ __('Remove line') }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totals & actions --}}
        <div class="border-t border-slate-100 bg-slate-50/40 px-5 py-5 sm:px-6">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <p class="max-w-md text-xs text-slate-500">
                    {{ __('Review totals before saving. Draft invoices can be edited later; confirmed invoices post to accounts and inventory.') }}
                </p>

                <div class="w-full max-w-xs space-y-2 lg:text-right">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-500">{{ __('Subtotal') }}</span>
                        <span class="font-mono font-medium text-slate-900">{{ \App\Support\Money::format($totals['subtotal']) }}</span>
                    </div>
                    @unless ($isNonGst)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">{{ __('GST') }}</span>
                            <span class="font-mono font-medium text-slate-900">{{ \App\Support\Money::format($totals['tax']) }}</span>
                        </div>
                    @endunless
                    <div class="flex items-center justify-between border-t border-slate-200/80 pt-2 text-base">
                        <span class="font-semibold text-slate-900">{{ __('Total') }}</span>
                        <span class="font-mono font-bold text-slate-900">{{ \App\Support\Money::format($totals['total']) }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">
                <a href="{{ route('sales.invoices.index') }}" wire:navigate class="inline-flex items-center justify-center text-sm font-medium text-slate-600 transition hover:text-slate-900">
                    {{ __('Cancel') }}
                </a>

                @if ($editing)
                    @can('delete', $invoice)
                        <x-secondary-button type="button" wire:click="deleteDraft" wire:confirm="{{ __('Delete this draft invoice?') }}" class="!border-red-200 !text-red-700 hover:!bg-red-50">
                            {{ __('Delete Draft') }}
                        </x-secondary-button>
                    @endcan
                @endif

                <x-form-actions>
                    <x-secondary-button type="button" wire:click="saveDraft" data-shortcut-save>
                        {{ $editing ? __('Save Draft') : __('Save as Draft') }}
                    </x-secondary-button>

                    @if ($editing)
                        @can('confirm', $invoice)
                            <x-primary-button type="button" wire:click="confirm" data-shortcut-primary>
                                {{ __('Confirm Invoice') }}
                            </x-primary-button>
                        @endcan
                    @else
                        <x-primary-button type="button" wire:click="confirm" data-shortcut-primary>
                            {{ __('Save & Confirm') }}
                        </x-primary-button>
                    @endif
                </x-form-actions>
            </div>
        </div>
    </x-card>
</div>
