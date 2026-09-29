<?php

namespace App\Domain\Authorization;

final class Permissions
{
    public const VIEW_DASHBOARD = 'view_dashboard';

    public const MANAGE_USERS = 'manage_users';

    public const MANAGE_ROLES = 'manage_roles';

    public const MANAGE_SETTINGS = 'manage_settings';

    public const VIEW_INVOICE = 'view_invoice';

    public const CREATE_INVOICE = 'create_invoice';

    public const EDIT_INVOICE = 'edit_invoice';

    public const DELETE_INVOICE = 'delete_invoice';

    public const CANCEL_INVOICE = 'cancel_invoice';

    public const REVERSE_INVOICE = 'reverse_invoice';

    public const VIEW_PURCHASE = 'view_purchase';

    public const CREATE_PURCHASE = 'create_purchase';

    public const EDIT_PURCHASE = 'edit_purchase';

    public const DELETE_PURCHASE = 'delete_purchase';

    public const CANCEL_PURCHASE = 'cancel_purchase';

    public const REVERSE_PURCHASE = 'reverse_purchase';

    public const VIEW_PAYMENT = 'view_payment';

    public const CREATE_PAYMENT = 'create_payment';

    public const REVERSE_PAYMENT = 'reverse_payment';

    public const VIEW_EXPENSE = 'view_expense';

    public const CREATE_EXPENSE = 'create_expense';

    public const REVERSE_EXPENSE = 'reverse_expense';

    public const VIEW_LEDGER = 'view_ledger';

    public const VIEW_TRIAL_BALANCE = 'view_trial_balance';

    public const MANAGE_CHART_OF_ACCOUNTS = 'manage_chart_of_accounts';

    public const CREATE_JOURNAL = 'create_journal';

    public const VIEW_JOURNAL = 'view_journal';

    public const VIEW_PROFIT_LOSS = 'view_profit_loss';

    public const VIEW_BALANCE_SHEET = 'view_balance_sheet';

    public const CLOSE_FINANCIAL_YEAR = 'close_financial_year';

    public const MANAGE_PRODUCTS = 'manage_products';

    public const VIEW_PRODUCTS = 'view_products';

    public const VIEW_CUSTOMERS = 'view_customers';

    public const MANAGE_CUSTOMERS = 'manage_customers';

    public const VIEW_SUPPLIERS = 'view_suppliers';

    public const MANAGE_SUPPLIERS = 'manage_suppliers';

    public const MANAGE_CATEGORIES = 'manage_categories';

    public const MANAGE_UNITS = 'manage_units';

    public const MANAGE_INVENTORY = 'manage_inventory';

    public const MANAGE_GST = 'manage_gst';

    public const EXPORT_DATA = 'export_data';

    public const RESTORE_BACKUP = 'restore_backup';

    public const RESOLVE_SYNC_CONFLICT = 'resolve_sync_conflict';

    /**
     * @return array<string, array{group: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            self::VIEW_DASHBOARD => ['group' => 'general', 'description' => 'View dashboard'],
            self::MANAGE_USERS => ['group' => 'administration', 'description' => 'Manage business users'],
            self::MANAGE_ROLES => ['group' => 'administration', 'description' => 'Manage roles and permissions'],
            self::MANAGE_SETTINGS => ['group' => 'administration', 'description' => 'Manage business settings'],
            self::VIEW_INVOICE => ['group' => 'sales', 'description' => 'View sales invoices'],
            self::CREATE_INVOICE => ['group' => 'sales', 'description' => 'Create sales invoices'],
            self::EDIT_INVOICE => ['group' => 'sales', 'description' => 'Edit sales invoices'],
            self::DELETE_INVOICE => ['group' => 'sales', 'description' => 'Delete draft invoices'],
            self::CANCEL_INVOICE => ['group' => 'sales', 'description' => 'Cancel confirmed invoices'],
            self::REVERSE_INVOICE => ['group' => 'sales', 'description' => 'Reverse confirmed invoices'],
            self::VIEW_PURCHASE => ['group' => 'purchases', 'description' => 'View purchase bills'],
            self::CREATE_PURCHASE => ['group' => 'purchases', 'description' => 'Create purchase bills'],
            self::EDIT_PURCHASE => ['group' => 'purchases', 'description' => 'Edit draft purchase bills'],
            self::DELETE_PURCHASE => ['group' => 'purchases', 'description' => 'Delete draft purchase bills'],
            self::CANCEL_PURCHASE => ['group' => 'purchases', 'description' => 'Cancel confirmed purchase bills'],
            self::REVERSE_PURCHASE => ['group' => 'purchases', 'description' => 'Reverse confirmed purchase bills'],
            self::VIEW_PAYMENT => ['group' => 'payments', 'description' => 'View payments'],
            self::CREATE_PAYMENT => ['group' => 'payments', 'description' => 'Record payments'],
            self::REVERSE_PAYMENT => ['group' => 'payments', 'description' => 'Reverse confirmed payments'],
            self::VIEW_EXPENSE => ['group' => 'expenses', 'description' => 'View expenses'],
            self::CREATE_EXPENSE => ['group' => 'expenses', 'description' => 'Record expenses'],
            self::REVERSE_EXPENSE => ['group' => 'expenses', 'description' => 'Reverse confirmed expenses'],
            self::VIEW_LEDGER => ['group' => 'accounting', 'description' => 'View ledger'],
            self::VIEW_TRIAL_BALANCE => ['group' => 'accounting', 'description' => 'View trial balance'],
            self::MANAGE_CHART_OF_ACCOUNTS => ['group' => 'accounting', 'description' => 'Manage chart of accounts'],
            self::CREATE_JOURNAL => ['group' => 'accounting', 'description' => 'Create manual journals'],
            self::VIEW_JOURNAL => ['group' => 'accounting', 'description' => 'View journals'],
            self::VIEW_PROFIT_LOSS => ['group' => 'accounting', 'description' => 'View profit and loss'],
            self::VIEW_BALANCE_SHEET => ['group' => 'accounting', 'description' => 'View balance sheet'],
            self::CLOSE_FINANCIAL_YEAR => ['group' => 'accounting', 'description' => 'Close financial year'],
            self::MANAGE_PRODUCTS => ['group' => 'inventory', 'description' => 'Manage products'],
            self::VIEW_PRODUCTS => ['group' => 'inventory', 'description' => 'View products'],
            self::VIEW_CUSTOMERS => ['group' => 'masters', 'description' => 'View customers'],
            self::MANAGE_CUSTOMERS => ['group' => 'masters', 'description' => 'Manage customers'],
            self::VIEW_SUPPLIERS => ['group' => 'masters', 'description' => 'View suppliers'],
            self::MANAGE_SUPPLIERS => ['group' => 'masters', 'description' => 'Manage suppliers'],
            self::MANAGE_CATEGORIES => ['group' => 'masters', 'description' => 'Manage product categories'],
            self::MANAGE_UNITS => ['group' => 'masters', 'description' => 'Manage units of measure'],
            self::MANAGE_INVENTORY => ['group' => 'inventory', 'description' => 'Manage inventory'],
            self::MANAGE_GST => ['group' => 'gst', 'description' => 'Manage GST settings and reports'],
            self::EXPORT_DATA => ['group' => 'utilities', 'description' => 'Export business data'],
            self::RESTORE_BACKUP => ['group' => 'utilities', 'description' => 'Restore backups'],
            self::RESOLVE_SYNC_CONFLICT => ['group' => 'sync', 'description' => 'Resolve sync conflicts'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::definitions());
    }
}
