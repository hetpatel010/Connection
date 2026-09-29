<div>
    <div class="mb-6 grid gap-4 md:grid-cols-3">
        <div>
            <x-input-label for="partyId" :value="__('Party')" />
            <x-select-input wire:model.live="partyId" id="partyId" class="mt-1 block w-full">
                <option value="">{{ __('Select party') }}</option>
                @foreach ($parties as $party)
                    <option value="{{ $party->id }}">{{ $party->name }} ({{ $party->party_type->label() }})</option>
                @endforeach
            </x-select-input>
        </div>
        <div>
            <x-input-label for="fromDate" :value="__('From')" />
            <x-text-input wire:model.live="fromDate" id="fromDate" type="date" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="toDate" :value="__('To')" />
            <x-text-input wire:model.live="toDate" id="toDate" type="date" class="mt-1 block w-full" />
        </div>
    </div>

    @if ($statement !== null)
        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            <span class="font-semibold text-slate-900">{{ $statement['party']->name }}</span>
            · {{ $statement['receivable'] ? __('Receivable statement') : __('Payable statement') }}
            · {{ __('Closing balance') }}:
            <span class="font-mono font-semibold">{{ \App\Support\Money::format($statement['closing_balance']) }}</span>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Reference') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Debit') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Credit') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Balance') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($statement['lines'] as $line)
                        <tr wire:key="stmt-{{ $line['date'] }}-{{ $line['reference'] }}">
                            <td class="px-4 py-3 text-slate-600">{{ \Illuminate\Support\Carbon::parse($line['date'])->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-slate-900">{{ $line['reference'] }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-900">
                                {{ $line['debit'] > 0 ? \App\Support\Money::format($line['debit']) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-slate-900">
                                {{ $line['credit'] > 0 ? \App\Support\Money::format($line['credit']) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($line['balance']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($partyId !== '')
        <p class="text-sm text-slate-500">{{ __('Select a valid date range to generate the statement.') }}</p>
    @endif
</div>
