<div>
    <x-index-toolbar export-module="debit-notes" :export-search="$search">
        <x-slot:search>
            <x-search-input wire:model.live.debounce.300ms="search" :placeholder="__('Search debit notes...')" />
        </x-slot:search>
    </x-index-toolbar>

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Number') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Supplier') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Source Bill') }}</th>
                    <th class="px-4 py-3 text-left font-medium text-slate-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right font-medium text-slate-600">{{ __('Total') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($debitNotes as $debitNote)
                    <tr wire:key="debit-note-{{ $debitNote->id }}">
                        <td class="px-4 py-3 font-mono text-slate-900">{{ $debitNote->debit_note_number }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $debitNote->debit_note_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-slate-900">{{ $debitNote->party->name }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $debitNote->sourcePurchaseBill->bill_number }}</td>
                        <td class="px-4 py-3 capitalize text-slate-600">{{ $debitNote->status->value }}</td>
                        <td class="px-4 py-3 text-right font-mono text-slate-900">{{ \App\Support\Money::format($debitNote->total_amount) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ __('No debit notes found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $debitNotes->links() }}</div>
</div>
