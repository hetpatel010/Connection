<div>
    <div class="mb-6 grid gap-4 md:grid-cols-2">
        <div>
            <x-input-label for="fromDate" :value="__('From')" />
            <x-text-input wire:model.live="fromDate" id="fromDate" type="date" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="toDate" :value="__('To')" />
            <x-text-input wire:model.live="toDate" id="toDate" type="date" class="mt-1 block w-full" />
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">{{ __('Income') }}</div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['income'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $row['code'] }}</td>
                            <td class="px-4 py-3 text-slate-900">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">{{ __('No income recorded.') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr>
                        <td colspan="2" class="px-4 py-3 font-semibold text-slate-900">{{ __('Total Income') }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['total_income']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">{{ __('Expenses') }}</div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['expenses'] as $row)
                        <tr>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $row['code'] }}</td>
                            <td class="px-4 py-3 text-slate-900">{{ $row['name'] }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">{{ __('No expenses recorded.') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50">
                    <tr>
                        <td colspan="2" class="px-4 py-3 font-semibold text-slate-900">{{ __('Total Expenses') }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['total_expenses']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="mt-6 rounded-lg border border-indigo-200 bg-indigo-50 px-6 py-4">
        <div class="flex items-center justify-between">
            <span class="text-lg font-semibold text-indigo-900">{{ __('Net Profit') }}</span>
            <span class="text-xl font-mono font-bold {{ $report['net_profit'] >= 0 ? 'text-indigo-900' : 'text-red-700' }}">
                {{ \App\Support\Money::format($report['net_profit']) }}
            </span>
        </div>
    </div>
</div>
