<div>
    <form wire:submit="save" class="mx-auto max-w-3xl space-y-6">
        <input type="hidden" wire:model="paymentType" />

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="paymentDate" :value="__('Payment date')" />
                <x-text-input wire:model="paymentDate" id="paymentDate" type="date" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="partyId" :value="$paymentType === 'payment_out' ? __('Supplier') : __('Customer')" />
                <x-select-input wire:model.live="partyId" id="partyId" class="mt-1 block w-full">
                    <option value="">{{ __('Optional') }}</option>
                    @foreach ($parties as $party)
                        <option value="{{ $party->id }}">{{ $party->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="paymentMode" :value="__('Mode')" />
                <x-select-input wire:model="paymentMode" id="paymentMode" class="mt-1 block w-full">
                    <option value="cash">{{ __('Cash') }}</option>
                    <option value="bank">{{ __('Bank') }}</option>
                </x-select-input>
            </div>
            <div>
                <x-input-label for="accountId" :value="__('Account (optional override)')" />
                <x-select-input wire:model="accountId" id="accountId" class="mt-1 block w-full">
                    <option value="">{{ __('Default for mode') }}</option>
                    @foreach ($paymentAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                    @endforeach
                </x-select-input>
            </div>
            <div>
                <x-input-label for="amount" :value="__('Amount (₹)')" />
                <x-text-input wire:model="amount" id="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="reference" :value="__('Reference')" />
                <x-text-input wire:model="reference" id="reference" type="text" class="mt-1 block w-full" />
            </div>
        </div>

        @if ($partyId !== '' && $openDocuments->isNotEmpty())
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h3 class="text-sm font-medium text-slate-900">{{ __('Allocate to open documents') }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ __('Optional — link this payment to unpaid credit invoices or bills.') }}</p>
                </div>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Document') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Outstanding') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Allocate (₹)') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($openDocuments as $document)
                            @php
                                $outstanding = $allocationService->outstandingBalance($document);
                                $documentNumber = $document instanceof \App\Models\SalesInvoice
                                    ? $document->invoice_number
                                    : $document->bill_number;
                                $documentDate = $document instanceof \App\Models\SalesInvoice
                                    ? $document->invoice_date
                                    : $document->bill_date;
                            @endphp
                            <tr wire:key="open-doc-{{ $document->id }}">
                                <td class="px-4 py-3 font-mono text-slate-900">{{ $documentNumber }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $documentDate->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($outstanding) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <x-text-input
                                        wire:model="allocationAmounts.{{ $document->id }}"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="{{ \App\Support\Money::paiseToRupees($outstanding) }}"
                                        placeholder="0.00"
                                        class="w-32 text-right"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif ($partyId !== '')
            <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                {{ __('No open credit documents for this party.') }}
            </p>
        @endif

        <div>
            <x-input-label for="notes" :value="__('Notes')" />
            <x-textarea-input wire:model="notes" id="notes" rows="3" class="mt-1 block w-full" />
        </div>

        <div class="flex justify-end">
            <x-primary-button>{{ __('Record Payment') }}</x-primary-button>
        </div>
    </form>
</div>
