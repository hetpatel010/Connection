<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm lg:col-span-1">
        <h3 class="text-base font-semibold text-slate-900">{{ __('Add Unit') }}</h3>
        <form wire:submit="saveUnit" class="mt-4 space-y-4">
            <div>
                <x-input-label for="name" :value="__('Name')" />
                <x-text-input wire:model="name" id="name" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="symbol" :value="__('Symbol')" />
                <x-text-input wire:model="symbol" id="symbol" class="mt-1 block w-full" required />
            </div>
            <div>
                <x-input-label for="decimalPlaces" :value="__('Decimal places')" />
                <x-text-input wire:model="decimalPlaces" id="decimalPlaces" type="number" min="0" max="4" class="mt-1 block w-full" />
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="isDefault" type="checkbox" class="rounded border-slate-300 text-indigo-600" />
                {{ __('Default unit') }}
            </label>
            <x-primary-button>{{ __('Save Unit') }}</x-primary-button>
        </form>
    </div>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h3 class="text-base font-semibold text-slate-900">{{ __('Units') }}</h3>
            <x-export-excel-link module="units" />
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Symbol') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Decimals') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Default') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($units as $unit)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $unit->name }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $unit->symbol }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $unit->decimal_places }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $unit->is_default ? __('Yes') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('No units found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
