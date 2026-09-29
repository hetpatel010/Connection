<div>
    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="transactionDate" :value="__('Date')" />
                <x-text-input wire:model="transactionDate" id="transactionDate" type="date" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('transactionDate')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="description" :value="__('Narration')" />
                <x-text-input wire:model="description" id="description" type="text" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Account') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Debit') }}</th>
                        <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Credit') }}</th>
                        <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Description') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($lines as $index => $line)
                        <tr wire:key="journal-line-{{ $index }}">
                            <td class="px-4 py-3">
                                <x-select-input wire:model="lines.{{ $index }}.account_id" class="w-full">
                                    <option value="">{{ __('Select account') }}</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>
                                    @endforeach
                                </x-select-input>
                            </td>
                            <td class="px-4 py-3">
                                <x-text-input wire:model="lines.{{ $index }}.debit" type="number" step="0.01" min="0" class="w-full text-right" />
                            </td>
                            <td class="px-4 py-3">
                                <x-text-input wire:model="lines.{{ $index }}.credit" type="number" step="0.01" min="0" class="w-full text-right" />
                            </td>
                            <td class="px-4 py-3">
                                <x-text-input wire:model="lines.{{ $index }}.line_description" type="text" class="w-full" />
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" wire:click="removeLine({{ $index }})" class="text-sm text-red-600 hover:text-red-800">
                                    {{ __('Remove') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between">
            <button type="button" wire:click="addLine" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                {{ __('Add line') }}
            </button>
            <x-primary-button>{{ __('Post Journal') }}</x-primary-button>
        </div>
    </form>
</div>
