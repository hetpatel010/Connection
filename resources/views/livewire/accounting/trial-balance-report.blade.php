<div>
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Code') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Account') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Debit') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Credit') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($report['rows'] as $row)
                    <tr>
                        <td class="px-4 py-3 font-mono text-slate-700">{{ $row['code'] }}</td>
                        <td class="px-4 py-3 text-slate-900">{{ $row['name'] }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">
                            {{ $row['debit_balance'] > 0 ? \App\Support\Money::format($row['debit_balance']) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">
                            {{ $row['credit_balance'] > 0 ? \App\Support\Money::format($row['credit_balance']) : '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No balances to display.') }}</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50">
                <tr>
                    <td colspan="2" class="px-4 py-3 text-right font-semibold text-slate-900">{{ __('Totals') }}</td>
                    <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['total_debits']) }}</td>
                    <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['total_credits']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($report['total_debits'] !== $report['total_credits'])
        <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ __('Trial balance is out of balance. Review journal entries.') }}
        </div>
    @endif
</div>
