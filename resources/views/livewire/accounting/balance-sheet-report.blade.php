<div>
    <div class="mb-6 max-w-xs">
        <x-input-label for="asOfDate" :value="__('As of date')" />
        <x-text-input wire:model.live="asOfDate" id="asOfDate" type="date" class="mt-1 block w-full" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach (['assets' => __('Assets'), 'liabilities' => __('Liabilities'), 'equity' => __('Equity')] as $section => $label)
            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">{{ $label }}</div>
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($report[$section] as $row)
                            <tr>
                                <td class="px-4 py-3 font-mono text-slate-600">{{ $row['code'] }}</td>
                                <td class="px-4 py-3 text-slate-900">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">{{ __('No balances.') }}</td></tr>
                        @endforelse
                    </tbody>
                    @if ($section === 'assets')
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td colspan="2" class="px-4 py-3 font-semibold text-slate-900">{{ __('Total Assets') }}</td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['total_assets']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                    @if ($section === 'equity')
                        <tfoot class="bg-slate-50">
                            <tr>
                                <td colspan="2" class="px-4 py-3 font-semibold text-slate-900">{{ __('Total L + E') }}</td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['total_liabilities_and_equity']) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endforeach
    </div>

    @if ($report['total_assets'] !== $report['total_liabilities_and_equity'])
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ __('Balance sheet totals do not match. Review opening balances and journal entries.') }}
        </div>
    @endif
</div>
