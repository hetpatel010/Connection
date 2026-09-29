<div>
    <div class="mb-6">
        <x-input-label for="asOfDate" :value="__('As of date')" />
        <x-text-input wire:model.live="asOfDate" id="asOfDate" type="date" class="mt-1 block w-full max-w-xs" />
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Supplier') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Current (0–30d)') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('31–60d') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('61–90d') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('90+d') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($report['rows'] as $row)
                    <tr wire:key="ageing-{{ $row['party_id'] }}">
                        <td class="px-4 py-3 text-slate-900">{{ $row['party_name'] }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['current']) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['days_31_60']) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['days_61_90']) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($row['days_90_plus']) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($row['total_outstanding']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No outstanding payables.') }}</td></tr>
                @endforelse
            </tbody>
            @if ($report['rows']->isNotEmpty())
                <tfoot class="bg-slate-50">
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ __('Total') }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['totals']['current']) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['totals']['days_31_60']) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['totals']['days_61_90']) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900">{{ \App\Support\Money::format($report['totals']['days_90_plus']) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold text-indigo-900">{{ \App\Support\Money::format($report['totals']['total_outstanding']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
