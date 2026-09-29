<aside
    :class="{ 'app-sidebar-open': sidebarOpen }"
    x-on:open-app-sidebar.window="sidebarOpen = true"
    x-on:close-app-sidebar.window="sidebarOpen = false"
    x-on:livewire:navigated.window="sidebarOpen = false"
    x-on:keydown.escape.window="sidebarOpen = false"
    class="app-sidebar h-dvh shrink-0 flex-col bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 shadow-xl shadow-slate-950/20"
>
    <div class="flex min-w-0 shrink-0 items-center justify-between gap-3 overflow-hidden border-b border-white/10 px-4 py-3.5">
        <livewire:layout.app-brand theme="dark" class="block min-w-0" />

        <button
            type="button"
            x-on:click="sidebarOpen = false"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/25 lg:hidden"
            aria-label="{{ __('Close navigation menu') }}"
        >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav
        data-sidebar-nav
        x-data="{
            scrollToActive() {
                this.$nextTick(() => {
                    const active = this.$el.querySelector('[data-sidebar-active]');
                    if (active) {
                        active.scrollIntoView({ block: 'nearest' });
                    }
                });
            },
        }"
        x-init="scrollToActive()"
        @@livewire:navigated.window="scrollToActive()"
        class="flex-1 space-y-1 overflow-y-auto px-2 pb-3 pt-5 text-base scrollbar-thin"
    >
        @can('view_dashboard')
            <div class="mt-1 pb-1">
                <x-sidebar-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    <x-slot:icon><x-sidebar-icon name="dashboard" /></x-slot:icon>
                    {{ __('Dashboard') }}
                </x-sidebar-link>
            </div>
        @endcan

        @canany(['view_customers', 'view_suppliers', 'view_products', 'manage_categories', 'manage_units', 'manage_inventory'])
            <x-sidebar-section
                :title="__('Masters')"
                :open="request()->routeIs('masters.*', 'inventory.*')"
            >
                @can('view_customers')
                    <x-sidebar-link :href="route('masters.customers.index')" :active="request()->routeIs('masters.customers.*')">
                        <x-slot:icon><x-sidebar-icon name="customers" /></x-slot:icon>
                        {{ __('Customers') }}
                    </x-sidebar-link>
                @endcan
                @can('view_suppliers')
                    <x-sidebar-link :href="route('masters.suppliers.index')" :active="request()->routeIs('masters.suppliers.*')">
                        <x-slot:icon><x-sidebar-icon name="suppliers" /></x-slot:icon>
                        {{ __('Suppliers') }}
                    </x-sidebar-link>
                @endcan
                @can('view_products')
                    <x-sidebar-link :href="route('masters.products.index')" :active="request()->routeIs('masters.products.*')">
                        <x-slot:icon><x-sidebar-icon name="products" /></x-slot:icon>
                        {{ __('Products') }}
                    </x-sidebar-link>
                @endcan
                @can('manage_categories')
                    <x-sidebar-link :href="route('masters.categories.index')" :active="request()->routeIs('masters.categories.*')">
                        <x-slot:icon><x-sidebar-icon name="categories" /></x-slot:icon>
                        {{ __('Categories') }}
                    </x-sidebar-link>
                @endcan
                @can('manage_units')
                    <x-sidebar-link :href="route('masters.units.index')" :active="request()->routeIs('masters.units.*')">
                        <x-slot:icon><x-sidebar-icon name="units" /></x-slot:icon>
                        {{ __('Units') }}
                    </x-sidebar-link>
                @endcan
                @can('manage_inventory')
                    <x-sidebar-link :href="route('inventory.index')" :active="request()->routeIs('inventory.*')">
                        <x-slot:icon><x-sidebar-icon name="inventory" /></x-slot:icon>
                        {{ __('Inventory') }}
                    </x-sidebar-link>
                @endcan
            </x-sidebar-section>
        @endcanany

        @canany(['view_invoice', 'view_payment'])
            <x-sidebar-section
                :title="__('Sales')"
                :open="request()->routeIs('sales.*', 'payments.in.*')"
            >
                @can('view_invoice')
                    <x-sidebar-link :href="route('sales.invoices.index')" :active="request()->routeIs('sales.invoices.*')">
                        <x-slot:icon><x-sidebar-icon name="invoices" /></x-slot:icon>
                        {{ __('Invoices') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('sales.credit-notes.index')" :active="request()->routeIs('sales.credit-notes.*')">
                        <x-slot:icon><x-sidebar-icon name="credit-notes" /></x-slot:icon>
                        {{ __('Credit Notes') }}
                    </x-sidebar-link>
                @endcan
                @can('view_payment')
                    <x-sidebar-link :href="route('payments.in.create')" :active="request()->routeIs('payments.in.*')">
                        <x-slot:icon><x-sidebar-icon name="payment-in" /></x-slot:icon>
                        {{ __('Payment In') }}
                    </x-sidebar-link>
                @endcan
            </x-sidebar-section>
        @endcanany

        @canany(['view_purchase', 'create_payment', 'view_expense'])
            <x-sidebar-section
                :title="__('Purchases')"
                :open="request()->routeIs('purchases.*', 'payments.out.*', 'expenses.*')"
            >
                @can('view_purchase')
                    <x-sidebar-link :href="route('purchases.bills.index')" :active="request()->routeIs('purchases.bills.*')">
                        <x-slot:icon><x-sidebar-icon name="purchase-bills" /></x-slot:icon>
                        {{ __('Purchase Bills') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('purchases.debit-notes.index')" :active="request()->routeIs('purchases.debit-notes.*')">
                        <x-slot:icon><x-sidebar-icon name="debit-notes" /></x-slot:icon>
                        {{ __('Debit Notes') }}
                    </x-sidebar-link>
                @endcan
                @can('create_payment')
                    <x-sidebar-link :href="route('payments.out.create')" :active="request()->routeIs('payments.out.*')">
                        <x-slot:icon><x-sidebar-icon name="payment-out" /></x-slot:icon>
                        {{ __('Payment Out') }}
                    </x-sidebar-link>
                @endcan
                @can('view_expense')
                    <x-sidebar-link :href="route('expenses.index')" :active="request()->routeIs('expenses.*')">
                        <x-slot:icon><x-sidebar-icon name="expenses" /></x-slot:icon>
                        {{ __('Expenses') }}
                    </x-sidebar-link>
                @endcan
            </x-sidebar-section>
        @endcanany

        @canany(['manage_chart_of_accounts', 'create_journal', 'view_ledger', 'view_trial_balance', 'view_profit_loss', 'view_balance_sheet', 'manage_gst'])
            <x-sidebar-section
                :title="__('Accounting')"
                :open="request()->routeIs('accounting.*')"
            >
                @can('manage_chart_of_accounts')
                    <x-sidebar-link :href="route('accounting.accounts.index')" :active="request()->routeIs('accounting.accounts.*')">
                        <x-slot:icon><x-sidebar-icon name="chart-of-accounts" /></x-slot:icon>
                        {{ __('Chart of Accounts') }}
                    </x-sidebar-link>
                @endcan
                @can('create_journal')
                    <x-sidebar-link :href="route('accounting.journals.create')" :active="request()->routeIs('accounting.journals.*')">
                        <x-slot:icon><x-sidebar-icon name="journal-entry" /></x-slot:icon>
                        {{ __('Journal Entry') }}
                    </x-sidebar-link>
                @endcan
                @can('view_ledger')
                    <x-sidebar-link :href="route('accounting.ledger')" :active="request()->routeIs('accounting.ledger')">
                        <x-slot:icon><x-sidebar-icon name="ledger" /></x-slot:icon>
                        {{ __('Ledger') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('accounting.bank-reconciliation')" :active="request()->routeIs('accounting.bank-reconciliation')">
                        <x-slot:icon><x-sidebar-icon name="bank-reconciliation" /></x-slot:icon>
                        {{ __('Bank Reconciliation') }}
                    </x-sidebar-link>
                @endcan
                @can('view_trial_balance')
                    <x-sidebar-link :href="route('accounting.trial-balance')" :active="request()->routeIs('accounting.trial-balance')">
                        <x-slot:icon><x-sidebar-icon name="trial-balance" /></x-slot:icon>
                        {{ __('Trial Balance') }}
                    </x-sidebar-link>
                @endcan
                @can('view_profit_loss')
                    <x-sidebar-link :href="route('accounting.profit-loss')" :active="request()->routeIs('accounting.profit-loss')">
                        <x-slot:icon><x-sidebar-icon name="profit-loss" /></x-slot:icon>
                        {{ __('Profit & Loss') }}
                    </x-sidebar-link>
                @endcan
                @can('view_balance_sheet')
                    <x-sidebar-link :href="route('accounting.balance-sheet')" :active="request()->routeIs('accounting.balance-sheet')">
                        <x-slot:icon><x-sidebar-icon name="balance-sheet" /></x-slot:icon>
                        {{ __('Balance Sheet') }}
                    </x-sidebar-link>
                @endcan
                @can('manage_gst')
                    <x-sidebar-link :href="route('accounting.gst-summary')" :active="request()->routeIs('accounting.gst-summary')">
                        <x-slot:icon><x-sidebar-icon name="gst-summary" /></x-slot:icon>
                        {{ __('GST Summary') }}
                    </x-sidebar-link>
                @endcan
            </x-sidebar-section>
        @endcanany

        @canany(['view_invoice', 'view_purchase', 'view_payment'])
            <x-sidebar-section
                :title="__('Reports')"
                :open="request()->routeIs('reports.*')"
            >
                @can('view_invoice')
                    <x-sidebar-link :href="route('reports.receivable-ageing')" :active="request()->routeIs('reports.receivable-ageing')">
                        <x-slot:icon><x-sidebar-icon name="receivable-ageing" /></x-slot:icon>
                        {{ __('Receivable Ageing') }}
                    </x-sidebar-link>
                @endcan
                @can('view_purchase')
                    <x-sidebar-link :href="route('reports.payable-ageing')" :active="request()->routeIs('reports.payable-ageing')">
                        <x-slot:icon><x-sidebar-icon name="payable-ageing" /></x-slot:icon>
                        {{ __('Payable Ageing') }}
                    </x-sidebar-link>
                @endcan
                @can('view_payment')
                    <x-sidebar-link :href="route('reports.party-statement')" :active="request()->routeIs('reports.party-statement')">
                        <x-slot:icon><x-sidebar-icon name="party-statement" /></x-slot:icon>
                        {{ __('Party Statement') }}
                    </x-sidebar-link>
                @endcan
            </x-sidebar-section>
        @endcanany

        @canany(['manage_settings', 'manage_users', 'manage_roles', 'close_financial_year', 'export_data'])
            <x-sidebar-section
                :title="__('Settings')"
                :open="request()->routeIs('settings.*')"
            >
                @can('manage_settings')
                    <x-sidebar-link :href="route('settings.business')" :active="request()->routeIs('settings.business')">
                        <x-slot:icon><x-sidebar-icon name="business-settings" /></x-slot:icon>
                        {{ __('Company Settings') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('settings.invoice-templates')" :active="request()->routeIs('settings.invoice-templates*')">
                        <x-slot:icon><x-sidebar-icon name="invoices" /></x-slot:icon>
                        {{ __('Invoice Templates') }}
                    </x-sidebar-link>
                @endcan
                @can('exportData', business_context()->business())
                    <x-sidebar-link :href="route('settings.export.index')" :active="request()->routeIs('settings.export*')">
                        <x-slot:icon><x-sidebar-icon name="gst-summary" /></x-slot:icon>
                        {{ __('Export Data') }}
                    </x-sidebar-link>
                @endcan
                @can('manage_users')
                    <x-sidebar-link :href="route('settings.users')" :active="request()->routeIs('settings.users')">
                        <x-slot:icon><x-sidebar-icon name="team-members" /></x-slot:icon>
                        {{ __('Team Members') }}
                    </x-sidebar-link>
                @endcan
                @can('manage_roles')
                    <x-sidebar-link :href="route('settings.roles')" :active="request()->routeIs('settings.roles')">
                        <x-slot:icon><x-sidebar-icon name="roles" /></x-slot:icon>
                        {{ __('Roles & Permissions') }}
                    </x-sidebar-link>
                @endcan
                @can('close_financial_year')
                    <x-sidebar-link :href="route('settings.financial-years')" :active="request()->routeIs('settings.financial-years')">
                        <x-slot:icon><x-sidebar-icon name="financial-years" /></x-slot:icon>
                        {{ __('Financial Years') }}
                    </x-sidebar-link>
                @endcan
            </x-sidebar-section>
        @endcanany
    </nav>

    <div class="shrink-0 border-t border-white/10 px-4 py-4">
        <x-vendor-credit compact theme="dark" />
    </div>
</aside>
