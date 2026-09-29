<div class="mx-auto max-w-3xl space-y-6">
    <x-card>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-slate-900">{{ __('Excel export (.xlsx)') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('Multi-sheet workbook with sales invoices, purchase bills, parties, products, payments, expenses, and chart of accounts for :business · FY :year.', [
                            'business' => $business->name,
                            'year' => $financialYear->label,
                        ]) }}
                    </p>
                    <a href="{{ route('settings.export.excel') }}"
                        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        {{ __('Download Excel') }}
                    </a>
                </div>
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-slate-900">{{ __('JSON backup (ZIP)') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ __('Full machine-readable backup including nested line items and GST summary — useful for migrations or developer integrations.') }}</p>
                    <a href="{{ route('settings.export.backup') }}"
                        class="mt-4 inline-flex items-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        {{ __('Download backup (ZIP)') }}
                    </a>
                </div>
            </div>
        </div>
    </x-card>
</div>
