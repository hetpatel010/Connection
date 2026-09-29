<?php

namespace App\Domain\Authorization;

final class SystemRoles
{
    public const OWNER = 'owner';

    public const ADMINISTRATOR = 'administrator';

    public const ACCOUNTANT = 'accountant';

    public const MANAGER = 'manager';

    public const SALESPERSON = 'salesperson';

    public const CASHIER = 'cashier';

    public const INVENTORY_MANAGER = 'inventory_manager';

    /**
     * @return array<string, array{name: string, description: string, permissions: list<string>}>
     */
    public static function definitions(): array
    {
        $all = Permissions::all();

        return [
            self::OWNER => [
                'name' => 'Owner',
                'description' => 'Full access to all business features',
                'permissions' => $all,
            ],
            self::ADMINISTRATOR => [
                'name' => 'Administrator',
                'description' => 'Manage users, settings, and all transactions',
                'permissions' => array_values(array_diff($all, [Permissions::RESTORE_BACKUP])),
            ],
            self::ACCOUNTANT => [
                'name' => 'Accountant',
                'description' => 'Accounting, reports, and financial year management',
                'permissions' => [
                    Permissions::VIEW_DASHBOARD,
                    Permissions::VIEW_INVOICE,
                    Permissions::VIEW_PURCHASE,
                    Permissions::VIEW_PAYMENT,
                    Permissions::VIEW_EXPENSE,
                    Permissions::VIEW_LEDGER,
                    Permissions::VIEW_TRIAL_BALANCE,
                    Permissions::MANAGE_CHART_OF_ACCOUNTS,
                    Permissions::CREATE_JOURNAL,
                    Permissions::VIEW_JOURNAL,
                    Permissions::VIEW_PROFIT_LOSS,
                    Permissions::VIEW_BALANCE_SHEET,
                    Permissions::CLOSE_FINANCIAL_YEAR,
                    Permissions::MANAGE_GST,
                    Permissions::EXPORT_DATA,
                ],
            ],
            self::MANAGER => [
                'name' => 'Manager',
                'description' => 'Operational oversight across sales and inventory',
                'permissions' => [
                    Permissions::VIEW_DASHBOARD,
                    Permissions::VIEW_INVOICE,
                    Permissions::CREATE_INVOICE,
                    Permissions::EDIT_INVOICE,
                    Permissions::VIEW_PURCHASE,
                    Permissions::CREATE_PURCHASE,
                    Permissions::VIEW_CUSTOMERS,
                    Permissions::VIEW_SUPPLIERS,
                    Permissions::VIEW_PRODUCTS,
                    Permissions::MANAGE_PRODUCTS,
                    Permissions::VIEW_PAYMENT,
                    Permissions::CREATE_PAYMENT,
                    Permissions::VIEW_EXPENSE,
                    Permissions::CREATE_EXPENSE,
                    Permissions::MANAGE_INVENTORY,
                    Permissions::EXPORT_DATA,
                ],
            ],
            self::SALESPERSON => [
                'name' => 'Salesperson',
                'description' => 'Create and manage sales documents',
                'permissions' => [
                    Permissions::VIEW_DASHBOARD,
                    Permissions::VIEW_INVOICE,
                    Permissions::CREATE_INVOICE,
                    Permissions::EDIT_INVOICE,
                    Permissions::VIEW_CUSTOMERS,
                    Permissions::VIEW_PRODUCTS,
                ],
            ],
            self::CASHIER => [
                'name' => 'Cashier',
                'description' => 'Process payments and view invoices',
                'permissions' => [
                    Permissions::VIEW_DASHBOARD,
                    Permissions::VIEW_INVOICE,
                    Permissions::CREATE_INVOICE,
                    Permissions::VIEW_PAYMENT,
                    Permissions::CREATE_PAYMENT,
                ],
            ],
            self::INVENTORY_MANAGER => [
                'name' => 'Inventory Manager',
                'description' => 'Manage products and stock',
                'permissions' => [
                    Permissions::VIEW_DASHBOARD,
                    Permissions::VIEW_PRODUCTS,
                    Permissions::MANAGE_PRODUCTS,
                    Permissions::MANAGE_CATEGORIES,
                    Permissions::MANAGE_UNITS,
                    Permissions::MANAGE_INVENTORY,
                ],
            ],
        ];
    }
}
