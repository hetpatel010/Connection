<?php

namespace App\Support;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class GlobalSearchRegistry
{
    /**
     * @return list<array{id: string, label: string, group: string, url: string, keywords: list<string>}>
     */
    public static function items(?User $user = null, ?Business $business = null): array
    {
        $user ??= auth()->user();

        if ($user === null) {
            return [];
        }

        $business ??= business_context()->business();

        $items = [];

        foreach (self::definitions() as $definition) {
            if (($definition['requires_business'] ?? false) && $business === null) {
                continue;
            }

            if (! self::canAccess($definition, $user, $business)) {
                continue;
            }

            $items[] = [
                'id' => $definition['id'],
                'label' => __($definition['label']),
                'group' => __($definition['group']),
                'url' => route($definition['route']),
                'keywords' => array_map(
                    fn (string $keyword): string => __($keyword),
                    $definition['keywords'] ?? [],
                ),
            ];
        }

        return $items;
    }

    /**
     * @param  array{id: string, label: string, group: string, route: string, requires_business?: bool, permission?: string, permissions?: list<string>, policy?: string, keywords?: list<string>}  $definition
     */
    private static function canAccess(array $definition, User $user, ?Business $business): bool
    {
        if (isset($definition['policy'])) {
            if ($business === null) {
                return false;
            }

            return Gate::forUser($user)->allows($definition['policy'], $business);
        }

        if ($business === null) {
            return ! isset($definition['permission']) && ! isset($definition['permissions']);
        }

        if (isset($definition['permissions'])) {
            foreach ($definition['permissions'] as $permission) {
                if ($user->hasPermissionInBusiness($permission, $business)) {
                    return true;
                }
            }

            return false;
        }

        if (isset($definition['permission'])) {
            return $user->hasPermissionInBusiness($definition['permission'], $business);
        }

        return true;
    }

    /**
     * @return list<array{id: string, label: string, group: string, route: string, requires_business?: bool, permission?: string, permissions?: list<string>, policy?: string, keywords?: list<string>}>
     */
    private static function definitions(): array
    {
        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'group' => 'General',
                'route' => 'dashboard',
                'requires_business' => true,
                'permission' => 'view_dashboard',
                'keywords' => ['home', 'overview'],
            ],
            [
                'id' => 'profile',
                'label' => 'Profile',
                'group' => 'General',
                'route' => 'profile',
                'requires_business' => true,
                'keywords' => ['account', 'user'],
            ],
            [
                'id' => 'about',
                'label' => 'About & Support',
                'group' => 'General',
                'route' => 'about',
                'requires_business' => true,
                'keywords' => ['help', 'support'],
            ],
            [
                'id' => 'businesses-create',
                'label' => 'Add Business',
                'group' => 'General',
                'route' => 'businesses.create',
                'keywords' => ['company', 'organization'],
            ],
            [
                'id' => 'customers',
                'label' => 'Customers',
                'group' => 'Masters',
                'route' => 'masters.customers.index',
                'requires_business' => true,
                'permission' => 'view_customers',
                'keywords' => ['parties', 'buyers'],
            ],
            [
                'id' => 'customers-create',
                'label' => 'Add Customer',
                'group' => 'Masters',
                'route' => 'masters.customers.create',
                'requires_business' => true,
                'permission' => 'manage_customers',
                'keywords' => ['new customer', 'party'],
            ],
            [
                'id' => 'suppliers',
                'label' => 'Suppliers',
                'group' => 'Masters',
                'route' => 'masters.suppliers.index',
                'requires_business' => true,
                'permission' => 'view_suppliers',
                'keywords' => ['vendors', 'parties'],
            ],
            [
                'id' => 'suppliers-create',
                'label' => 'Add Supplier',
                'group' => 'Masters',
                'route' => 'masters.suppliers.create',
                'requires_business' => true,
                'permission' => 'manage_suppliers',
                'keywords' => ['new supplier', 'vendor'],
            ],
            [
                'id' => 'products',
                'label' => 'Products',
                'group' => 'Masters',
                'route' => 'masters.products.index',
                'requires_business' => true,
                'permission' => 'view_products',
                'keywords' => ['items', 'stock', 'catalog'],
            ],
            [
                'id' => 'products-create',
                'label' => 'Add Product',
                'group' => 'Masters',
                'route' => 'masters.products.create',
                'requires_business' => true,
                'permission' => 'manage_products',
                'keywords' => ['new product', 'item'],
            ],
            [
                'id' => 'categories',
                'label' => 'Categories',
                'group' => 'Masters',
                'route' => 'masters.categories.index',
                'requires_business' => true,
                'permission' => 'manage_categories',
                'keywords' => ['product categories'],
            ],
            [
                'id' => 'units',
                'label' => 'Units',
                'group' => 'Masters',
                'route' => 'masters.units.index',
                'requires_business' => true,
                'permission' => 'manage_units',
                'keywords' => ['uom', 'measurement'],
            ],
            [
                'id' => 'inventory',
                'label' => 'Inventory',
                'group' => 'Masters',
                'route' => 'inventory.index',
                'requires_business' => true,
                'permission' => 'manage_inventory',
                'keywords' => ['stock', 'warehouse'],
            ],
            [
                'id' => 'invoices',
                'label' => 'Invoices',
                'group' => 'Sales',
                'route' => 'sales.invoices.index',
                'requires_business' => true,
                'permission' => 'view_invoice',
                'keywords' => ['sales', 'billing'],
            ],
            [
                'id' => 'invoices-create',
                'label' => 'New Invoice',
                'group' => 'Sales',
                'route' => 'sales.invoices.create',
                'requires_business' => true,
                'permission' => 'create_invoice',
                'keywords' => ['create invoice', 'sales bill'],
            ],
            [
                'id' => 'credit-notes',
                'label' => 'Credit Notes',
                'group' => 'Sales',
                'route' => 'sales.credit-notes.index',
                'requires_business' => true,
                'permission' => 'view_invoice',
                'keywords' => ['returns', 'refund'],
            ],
            [
                'id' => 'payment-in',
                'label' => 'Payment In',
                'group' => 'Sales',
                'route' => 'payments.in.create',
                'requires_business' => true,
                'permissions' => ['view_payment', 'create_payment'],
                'keywords' => ['receipt', 'collection'],
            ],
            [
                'id' => 'purchase-bills',
                'label' => 'Purchase Bills',
                'group' => 'Purchases',
                'route' => 'purchases.bills.index',
                'requires_business' => true,
                'permission' => 'view_purchase',
                'keywords' => ['purchases', 'vendor bills'],
            ],
            [
                'id' => 'purchase-bills-create',
                'label' => 'New Purchase Bill',
                'group' => 'Purchases',
                'route' => 'purchases.bills.create',
                'requires_business' => true,
                'permission' => 'create_purchase',
                'keywords' => ['create purchase', 'vendor bill'],
            ],
            [
                'id' => 'debit-notes',
                'label' => 'Debit Notes',
                'group' => 'Purchases',
                'route' => 'purchases.debit-notes.index',
                'requires_business' => true,
                'permission' => 'view_purchase',
                'keywords' => ['purchase return'],
            ],
            [
                'id' => 'payment-out',
                'label' => 'Payment Out',
                'group' => 'Purchases',
                'route' => 'payments.out.create',
                'requires_business' => true,
                'permission' => 'create_payment',
                'keywords' => ['payment', 'disbursement'],
            ],
            [
                'id' => 'payments',
                'label' => 'Payments',
                'group' => 'Purchases',
                'route' => 'payments.index',
                'requires_business' => true,
                'permission' => 'view_payment',
                'keywords' => ['receipts', 'transactions'],
            ],
            [
                'id' => 'expenses',
                'label' => 'Expenses',
                'group' => 'Purchases',
                'route' => 'expenses.index',
                'requires_business' => true,
                'permission' => 'view_expense',
                'keywords' => ['costs', 'spending'],
            ],
            [
                'id' => 'expenses-create',
                'label' => 'New Expense',
                'group' => 'Purchases',
                'route' => 'expenses.create',
                'requires_business' => true,
                'permission' => 'create_expense',
                'keywords' => ['add expense', 'record expense'],
            ],
            [
                'id' => 'chart-of-accounts',
                'label' => 'Chart of Accounts',
                'group' => 'Accounting',
                'route' => 'accounting.accounts.index',
                'requires_business' => true,
                'permission' => 'manage_chart_of_accounts',
                'keywords' => ['accounts', 'coa'],
            ],
            [
                'id' => 'journal-entry',
                'label' => 'Journal Entry',
                'group' => 'Accounting',
                'route' => 'accounting.journals.create',
                'requires_business' => true,
                'permission' => 'create_journal',
                'keywords' => ['manual journal', 'voucher'],
            ],
            [
                'id' => 'ledger',
                'label' => 'Ledger',
                'group' => 'Accounting',
                'route' => 'accounting.ledger',
                'requires_business' => true,
                'permission' => 'view_ledger',
                'keywords' => ['account ledger', 'report'],
            ],
            [
                'id' => 'bank-reconciliation',
                'label' => 'Bank Reconciliation',
                'group' => 'Accounting',
                'route' => 'accounting.bank-reconciliation',
                'requires_business' => true,
                'permission' => 'view_ledger',
                'keywords' => ['bank', 'statement'],
            ],
            [
                'id' => 'trial-balance',
                'label' => 'Trial Balance',
                'group' => 'Accounting',
                'route' => 'accounting.trial-balance',
                'requires_business' => true,
                'permission' => 'view_trial_balance',
                'keywords' => ['tb', 'report'],
            ],
            [
                'id' => 'profit-loss',
                'label' => 'Profit & Loss',
                'group' => 'Accounting',
                'route' => 'accounting.profit-loss',
                'requires_business' => true,
                'permission' => 'view_profit_loss',
                'keywords' => ['p&l', 'income statement'],
            ],
            [
                'id' => 'balance-sheet',
                'label' => 'Balance Sheet',
                'group' => 'Accounting',
                'route' => 'accounting.balance-sheet',
                'requires_business' => true,
                'permission' => 'view_balance_sheet',
                'keywords' => ['assets', 'liabilities'],
            ],
            [
                'id' => 'gst-summary',
                'label' => 'GST Summary',
                'group' => 'Accounting',
                'route' => 'accounting.gst-summary',
                'requires_business' => true,
                'permission' => 'manage_gst',
                'keywords' => ['gst', 'tax', 'gstr'],
            ],
            [
                'id' => 'receivable-ageing',
                'label' => 'Receivable Ageing',
                'group' => 'Reports',
                'route' => 'reports.receivable-ageing',
                'requires_business' => true,
                'permission' => 'view_invoice',
                'keywords' => ['aging', 'outstanding', 'debtors'],
            ],
            [
                'id' => 'payable-ageing',
                'label' => 'Payable Ageing',
                'group' => 'Reports',
                'route' => 'reports.payable-ageing',
                'requires_business' => true,
                'permission' => 'view_purchase',
                'keywords' => ['aging', 'creditors'],
            ],
            [
                'id' => 'party-statement',
                'label' => 'Party Statement',
                'group' => 'Reports',
                'route' => 'reports.party-statement',
                'requires_business' => true,
                'permission' => 'view_payment',
                'keywords' => ['ledger', 'customer statement'],
            ],
            [
                'id' => 'company-settings',
                'label' => 'Company Settings',
                'group' => 'Settings',
                'route' => 'settings.business',
                'requires_business' => true,
                'permission' => 'manage_settings',
                'keywords' => ['business', 'logo', 'gstin'],
            ],
            [
                'id' => 'invoice-templates',
                'label' => 'Invoice Templates',
                'group' => 'Settings',
                'route' => 'settings.invoice-templates',
                'requires_business' => true,
                'permission' => 'manage_settings',
                'keywords' => ['templates', 'design'],
            ],
            [
                'id' => 'export-data',
                'label' => 'Export Data',
                'group' => 'Settings',
                'route' => 'settings.export.index',
                'requires_business' => true,
                'policy' => 'exportData',
                'keywords' => ['backup', 'download', 'excel'],
            ],
            [
                'id' => 'team-members',
                'label' => 'Team Members',
                'group' => 'Settings',
                'route' => 'settings.users',
                'requires_business' => true,
                'permission' => 'manage_users',
                'keywords' => ['users', 'staff'],
            ],
            [
                'id' => 'roles',
                'label' => 'Roles & Permissions',
                'group' => 'Settings',
                'route' => 'settings.roles',
                'requires_business' => true,
                'permission' => 'manage_roles',
                'keywords' => ['access', 'authorization'],
            ],
            [
                'id' => 'financial-years',
                'label' => 'Financial Years',
                'group' => 'Settings',
                'route' => 'settings.financial-years',
                'requires_business' => true,
                'permission' => 'close_financial_year',
                'keywords' => ['fy', 'year end'],
            ],
        ];
    }
}
