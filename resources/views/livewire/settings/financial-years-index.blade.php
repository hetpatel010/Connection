<div>
    @if ($canOpenNext)
        <div class="mb-6 flex flex-col gap-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-medium text-emerald-900">{{ __('Ready for the next financial year') }}</p>
                <p class="text-sm text-emerald-800">{{ __('The latest year is closed. Open the next Indian financial year to continue posting transactions.') }}</p>
            </div>
            <button type="button" wire:click="openNextFinancialYear"
                class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                {{ __('Open next financial year') }}
            </button>
        </div>
    @endif

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Label') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Period') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Current') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Closed') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($financialYears as $financialYear)
                    <tr wire:key="financial-year-{{ $financialYear->id }}">
                        <td class="px-4 py-3 font-medium text-slate-900">FY {{ $financialYear->label }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $financialYear->starts_on->format('d M Y') }} – {{ $financialYear->ends_on->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 capitalize text-slate-600">{{ $financialYear->status->value }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($financialYear->is_current)
                                <span class="rounded bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">{{ __('Current') }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($financialYear->closed_at)
                                <span>{{ $financialYear->closed_at->format('d M Y') }}</span>
                                @if ($financialYear->closedByUser)
                                    <span class="block text-xs text-slate-500">{{ $financialYear->closedByUser->name }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('close', $financialYear)
                                <button type="button" wire:click="closeFinancialYear('{{ $financialYear->id }}')"
                                    wire:confirm="{{ __('Close this financial year? No new transactions can be posted once closed.') }}"
                                    class="text-red-600 hover:text-red-800">
                                    {{ __('Close') }}
                                </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No financial years found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
