<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="grid flex-1 gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="fromDate" :value="__('From')" />
                <x-text-input wire:model.live="fromDate" id="fromDate" type="date" class="mt-1 block w-full" />
            </div>
            <div>
                <x-input-label for="toDate" :value="__('To')" />
                <x-text-input wire:model.live="toDate" id="toDate" type="date" class="mt-1 block w-full" />
            </div>
        </div>
        @if ($report['from'] && $report['to'])
            <a href="{{ route('accounting.gst-summary.gstr1-export', ['from' => $report['from'], 'to' => $report['to']]) }}"
                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                {{ __('Export GSTR-1 JSON') }}
            </a>
        @endif
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-600">{{ __('Output GST (Sales)') }}</p>
            <p class="mt-1 text-xl font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['output_tax']) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-600">{{ __('Input GST (Purchases & Expenses)') }}</p>
            <p class="mt-1 text-xl font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['input_tax']) }}</p>
        </div>
        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
            <p class="text-sm text-indigo-700">{{ __('Net GST Payable') }}</p>
            <p class="mt-1 text-xl font-mono font-bold text-indigo-900">{{ \App\Support\Money::format($report['net_payable']) }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">{{ __('Output GST by Rate') }}</div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Rate') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Taxable') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Tax') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['output_by_rate'] as $row)
                        <tr>
                            <td class="px-4 py-3 text-slate-900">{{ $row['tax_rate'] }}%</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['taxable_amount']) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['tax_amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">{{ __('No sales GST in this period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-900">{{ __('Input GST by Rate') }}</div>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Rate') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Taxable') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Tax') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['input_by_rate'] as $row)
                        <tr>
                            <td class="px-4 py-3 text-slate-900">{{ $row['tax_rate'] }}%</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['taxable_amount']) }}</td>
                            <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['tax_amount']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">{{ __('No input GST in this period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
