<?php

namespace App\Support;

use Illuminate\Routing\Route;

final class RouteTitles
{
    /**
     * @var array<string, string>
     */
    private const MAP = [
        'login' => 'Log in',
        'register' => 'Register',
        'password.request' => 'Forgot Password',
        'password.reset' => 'Reset Password',
        'password.confirm' => 'Confirm Password',
        'verification.notice' => 'Verify Email',
        'onboarding.business' => 'Set Up Business',
        'dashboard' => 'Dashboard',
        'profile' => 'Profile',
        'about' => 'About & Support',
        'businesses.create' => 'Add Business',
        'settings.business' => 'Company Settings',
        'settings.users' => 'Team Members',
        'settings.roles' => 'Roles & Permissions',
        'settings.financial-years' => 'Financial Years',
        'settings.export.index' => 'Data Export',
        'settings.export.excel' => 'Excel Export',
        'settings.export.backup' => 'Backup Export',
        'settings.invoice-templates' => 'Invoice Templates',
        'settings.invoice-templates.preview' => 'Template Preview',
        'settings.invoice-templates.contact' => 'Email SVEO',
        'masters.customers.index' => 'Customers',
        'masters.customers.create' => 'Add Customer',
        'masters.customers.edit' => 'Edit Customer',
        'masters.suppliers.index' => 'Suppliers',
        'masters.suppliers.create' => 'Add Supplier',
        'masters.suppliers.edit' => 'Edit Supplier',
        'masters.products.index' => 'Products',
        'masters.products.create' => 'Add Product',
        'masters.products.edit' => 'Edit Product',
        'masters.categories.index' => 'Categories',
        'masters.units.index' => 'Units',
        'sales.invoices.index' => 'Invoices',
        'sales.invoices.create' => 'New Invoice',
        'sales.invoices.edit' => 'Edit Invoice',
        'sales.invoices.print' => 'Print Invoice',
        'sales.invoices.download' => 'Download Invoice',
        'sales.credit-notes.index' => 'Credit Notes',
        'purchases.bills.index' => 'Purchase Bills',
        'purchases.bills.create' => 'New Purchase Bill',
        'purchases.bills.edit' => 'Edit Purchase Bill',
        'purchases.bills.print' => 'Print Purchase Bill',
        'purchases.bills.download' => 'Download Purchase Bill',
        'purchases.debit-notes.index' => 'Debit Notes',
        'payments.index' => 'Payments',
        'payments.in.create' => 'Payment In',
        'payments.out.create' => 'Payment Out',
        'expenses.index' => 'Expenses',
        'expenses.create' => 'New Expense',
        'inventory.index' => 'Inventory',
        'inventory.ledger' => 'Stock Ledger',
        'inventory.ledger.export' => 'Export Stock Ledger',
        'inventory.adjust' => 'Adjust Stock',
        'accounting.accounts.index' => 'Chart of Accounts',
        'accounting.accounts.create' => 'Add Account',
        'accounting.accounts.show' => 'Account Details',
        'accounting.accounts.edit' => 'Edit Account',
        'accounting.journals.create' => 'New Journal Entry',
        'accounting.ledger' => 'Ledger Report',
        'accounting.bank-reconciliation' => 'Bank Reconciliation',
        'accounting.trial-balance' => 'Trial Balance',
        'accounting.profit-loss' => 'Profit & Loss',
        'accounting.balance-sheet' => 'Balance Sheet',
        'accounting.gst-summary' => 'GST Summary',
        'accounting.gst-summary.gstr1-export' => 'GSTR-1 Export',
        'reports.receivable-ageing' => 'Receivable Ageing',
        'reports.payable-ageing' => 'Payable Ageing',
        'reports.party-statement' => 'Party Statement',
    ];

    public static function forRoute(?Route $route): ?string
    {
        $name = $route?->getName();

        if ($name === null) {
            return null;
        }

        $title = self::MAP[$name] ?? null;

        return $title !== null ? __($title) : null;
    }
}
