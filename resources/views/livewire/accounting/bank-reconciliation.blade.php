<div>
    <div class="mb-6">
        <label for="bank-account" class="block text-sm font-medium text-slate-700">{{ __('Bank account') }}</label>
        <x-select-input wire:model.live="accountId" id="bank-account" class="mt-1 max-w-xl">
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
            @endforeach
        </x-select-input>
    </div>

    @if ($selectedAccount)
        <div class="mb-6 grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Unmatched statement lines') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Select') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Description') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($statementLines as $line)
                            <tr @class(['bg-indigo-50' => $selectedLineId === $line->id])>
                                <td class="px-4 py-3">
                                    <input type="radio" wire:model.live="selectedLineId" value="{{ $line->id }}" name="selectedLine">
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $line->transaction_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $line->description ?: '—' }}
                                    @if ($line->reference)
                                        <span class="block text-xs text-slate-400">{{ $line->reference }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-slate-900">
                                    {{ \App\Support\Money::format(abs($line->amount)) }}
                                    {{ $line->amount >= 0 ? __('IN') : __('OUT') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No unmatched statement lines.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">{{ __('Unmatched bank payments') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Select') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Payment') }}</th>
                            <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($payments as $payment)
                            <tr @class(['bg-indigo-50' => $selectedPaymentId === $payment->id])>
                                <td class="px-4 py-3">
                                    <input type="radio" wire:model.live="selectedPaymentId" value="{{ $payment->id }}" name="selectedPayment">
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $payment->payment_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <span class="font-mono text-slate-700">{{ $payment->payment_number }}</span>
                                    @if ($payment->party)
                                        <span class="block text-xs text-slate-400">{{ $payment->party->name }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-slate-900">
                                    {{ \App\Support\Money::format($payment->amount) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No unmatched bank payments.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="button"
                wire:click="match"
                wire:loading.attr="disabled"
                @disabled(! $selectedLineId || ! $selectedPaymentId)
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50">
                {{ __('Match selected') }}
            </button>
        </div>
    @endif
</div>
