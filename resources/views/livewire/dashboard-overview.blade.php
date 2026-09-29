<div>
    <x-page-header
        :title="__('Dashboard')"
        :description="business_context()->business()
            ? business_context()->business()->name . (business_context()->financialYear() ? ' · FY ' . business_context()->financialYear()->label : '')
            : null"
    >
        <x-slot:actions>
            @can('exportData', business_context()->business())
                <a href="{{ route('settings.export.excel') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    {{ __('Export Excel') }}
                </a>
            @endcan
            @can('create_invoice')
                <a href="{{ route('sales.invoices.create') }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-indigo-600/20 transition hover:bg-indigo-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    {{ __('New Invoice') }}
                </a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if (! ($metrics['setup']['is_complete'] ?? true))
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <h3 class="font-semibold text-amber-900">{{ __('Complete your setup') }}</h3>
            <ul class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                <li class="flex items-center gap-2 {{ ($metrics['setup']['has_gstin'] ?? false) ? 'text-emerald-700' : 'text-amber-800' }}">
                    <span>{{ ($metrics['setup']['has_gstin'] ?? false) ? '✓' : '○' }}</span> {{ __('Add GSTIN in settings') }}
                </li>
                <li class="flex items-center gap-2 {{ ($metrics['setup']['has_customers'] ?? false) ? 'text-emerald-700' : 'text-amber-800' }}">
                    <span>{{ ($metrics['setup']['has_customers'] ?? false) ? '✓' : '○' }}</span> {{ __('Add a customer') }}
                </li>
                <li class="flex items-center gap-2 {{ ($metrics['setup']['has_products'] ?? false) ? 'text-emerald-700' : 'text-amber-800' }}">
                    <span>{{ ($metrics['setup']['has_products'] ?? false) ? '✓' : '○' }}</span> {{ __('Add a product') }}
                </li>
                <li class="flex items-center gap-2 {{ ($metrics['setup']['has_invoices'] ?? false) ? 'text-emerald-700' : 'text-amber-800' }}">
                    <span>{{ ($metrics['setup']['has_invoices'] ?? false) ? '✓' : '○' }}</span> {{ __('Create first invoice') }}
                </li>
            </ul>
        </div>
    @endif

    <div class="mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-violet-700 p-6 text-white shadow-lg shadow-indigo-600/20 sm:p-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-100">{{ __('Welcome back') }}, {{ auth()->user()->name }}</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight">{{ __('Business overview') }}</h2>
                <p class="mt-2 max-w-xl text-sm text-indigo-100/90">{{ __('Track sales, GST, inventory alerts, and cash position for the active financial year.') }}</p>
            </div>
            <div class="rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-inset ring-white/15 backdrop-blur-sm">
                <p class="text-xs font-medium uppercase tracking-wider text-indigo-100">{{ __('Today') }}</p>
                <p class="mt-1 text-lg font-semibold">{{ now()->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-stat-card :label="__('Today\'s Sales')" :value="\App\Support\Money::format($metrics['todays_sales'])" tone="brand">
            <x-slot:icon><svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card :label="__('This Month')" :value="\App\Support\Money::format($metrics['monthly_sales'] ?? 0)">
            <x-slot:icon><svg class="h-5 w-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card :label="__('GST Collected (MTD)')" :value="\App\Support\Money::format($metrics['gst_collected_month'] ?? 0)" tone="brand">
            <x-slot:icon><svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25l6-6m4.5-3.493V21.75l-3.75-1.5-3.75 1.5-3.75-1.5-3.75 1.5V4.757c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0c1.1.128 1.907 1.077 1.907 2.185Z" /></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card :label="__('Low Stock Items')" :value="(string) ($metrics['low_stock_count'] ?? 0)" :tone="($metrics['low_stock_count'] ?? 0) > 0 ? 'warning' : null">
            <x-slot:icon><svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card :label="__('Receivables')" :value="\App\Support\Money::format($metrics['receivables'])" tone="warning">
            <x-slot:icon><svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></x-slot:icon>
        </x-stat-card>
        <x-stat-card :label="__('Cash & Bank')" :value="\App\Support\Money::format($metrics['cash_and_bank'])" tone="success">
            <x-slot:icon><svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" /></svg></x-slot:icon>
        </x-stat-card>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="font-semibold text-slate-900">{{ __('Recent Invoices') }}</h3>
                        <p class="text-sm text-slate-500">{{ __('Latest confirmed sales invoices') }}</p>
                    </div>
                    @can('view_invoice')
                        <a href="{{ route('sales.invoices.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ __('View all') }}</a>
                    @endcan
                </div>

                @if ($metrics['recent_invoices']->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50/80">
                                <tr>
                                    <th class="px-5 py-3 text-left font-medium text-slate-600">{{ __('Number') }}</th>
                                    <th class="px-5 py-3 text-left font-medium text-slate-600">{{ __('Date') }}</th>
                                    <th class="px-5 py-3 text-left font-medium text-slate-600">{{ __('Customer') }}</th>
                                    <th class="px-5 py-3 text-right font-medium text-slate-600">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($metrics['recent_invoices'] as $invoice)
                                    <tr wire:key="recent-invoice-{{ $invoice->id }}" class="hover:bg-slate-50/60">
                                        <td class="px-5 py-3.5 font-mono font-medium text-slate-900">{{ $invoice->invoice_number }}</td>
                                        <td class="px-5 py-3.5 text-slate-600">{{ $invoice->invoice_date->format('d M Y') }}</td>
                                        <td class="px-5 py-3.5 text-slate-900">{{ $invoice->party->name }}</td>
                                        <td class="px-5 py-3.5 text-right font-mono font-medium text-slate-900">{{ \App\Support\Money::format($invoice->total_amount) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="px-5 py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        </div>
                        <p class="mt-4 text-sm font-medium text-slate-900">{{ __('No invoices yet') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Create your first invoice to see activity here.') }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <h3 class="font-semibold text-slate-900">{{ __('Quick actions') }}</h3>
                <div class="mt-4 grid gap-2">
                    @can('create_invoice')
                        <a href="{{ route('sales.invoices.create') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600">+</span>
                            {{ __('Create invoice') }}
                        </a>
                    @endcan
                    @can('create_payment')
                        <a href="{{ route('payments.in.create') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">₹</span>
                            {{ __('Record payment in') }}
                        </a>
                    @endcan
                    @can('manage_inventory')
                        @if (($metrics['low_stock_count'] ?? 0) > 0)
                            <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 transition hover:bg-amber-100">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-700">!</span>
                                {{ __(':count low-stock items', ['count' => $metrics['low_stock_count']]) }}
                            </a>
                        @endif
                    @endcan
                    @can('view_customers')
                        <a href="{{ route('masters.customers.index') }}" class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-slate-50">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600">@</span>
                            {{ __('Manage customers') }}
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
