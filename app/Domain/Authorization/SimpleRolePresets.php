<?php

namespace App\Domain\Authorization;

final class SimpleRolePresets
{
    public const STAFF = 'staff';

    public const ACCOUNTANT = 'accountant_simple';

    public const VIEW_ONLY = 'view_only';

    /**
     * @return array<string, array{name: string, description: string, permissions: list<string>}>
     */
    public static function definitions(): array
    {
        return [
            self::STAFF => [
                'name' => 'Staff',
                'description' => 'Create invoices, record payments, view customers and products',
                'permissions' => [
                    Permissions::VIEW_DASHBOARD,
                    Permissions::VIEW_INVOICE,
                    Permissions::CREATE_INVOICE,
                    Permissions::EDIT_INVOICE,
                    Permissions::VIEW_CUSTOMERS,
                    Permissions::VIEW_PRODUCTS,
                    Permissions::VIEW_PAYMENT,
                    Permissions::CREATE_PAYMENT,
                ],
            ],
            self::ACCOUNTANT => [
                'name' => 'Accountant (Simple)',
                'description' => 'Full books, reports, GST, and exports — no user management',
                'permissions' => SystemRoles::definitions()[SystemRoles::ACCOUNTANT]['permissions'],
            ],
            self::VIEW_ONLY => [
                'name' => 'View Only',
                'description' => 'Read dashboard, invoices, purchases, and reports',
                'permissions' => [
                    Permissions::VIEW_DASHBOARD,
                    Permissions::VIEW_INVOICE,
                    Permissions::VIEW_PURCHASE,
                    Permissions::VIEW_PAYMENT,
                    Permissions::VIEW_EXPENSE,
                    Permissions::VIEW_CUSTOMERS,
                    Permissions::VIEW_SUPPLIERS,
                    Permissions::VIEW_PRODUCTS,
                    Permissions::VIEW_LEDGER,
                    Permissions::VIEW_TRIAL_BALANCE,
                    Permissions::VIEW_PROFIT_LOSS,
                    Permissions::VIEW_BALANCE_SHEET,
                ],
            ],
        ];
    }
}
