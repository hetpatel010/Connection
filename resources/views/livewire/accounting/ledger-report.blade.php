<div>
    <div class="mb-6">
        <label for="ledger-account" class="block text-sm font-medium text-slate-700">{{ __('Account') }}</label>
        <x-select-input wire:model.live="accountId" id="ledger-account" class="mt-1 max-w-xl">
            @foreach ($accounts as $account)
                <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
            @endforeach
        </x-select-input>
    </div>

    @if ($selectedAccount)
        <div class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            {{ __('Account') }}: <span class="font-medium text-slate-900">{{ $selectedAccount->code }} — {{ $selectedAccount->name }}</span>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Journal') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Description') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Debit') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Credit') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Balance') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($entries as $entry)
                    <tr>
                        <td class="px-4 py-3 text-slate-600">{{ $entry['date'] }}</td>
                        <td class="px-4 py-3 font-mono text-slate-700">{{ $entry['journal_number'] }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $entry['description'] }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">
                            {{ $entry['debit_amount'] > 0 ? \App\Support\Money::format($entry['debit_amount']) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">
                            {{ $entry['credit_amount'] > 0 ? \App\Support\Money::format($entry['credit_amount']) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">
                            {{ \App\Support\Money::format($entry['running_balance']) }} {{ strtoupper($entry['balance_side']->value) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No ledger entries for this account.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
