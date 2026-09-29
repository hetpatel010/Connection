<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Enums\AccountType;

final class DefaultChartOfAccounts
{
    /**
     * @return list<array{
     *     code: string,
     *     name: string,
     *     type: AccountType,
     *     group: string,
     *     parent_code: string|null,
     *     is_system: bool,
     *     description: string|null
     * }>
     */
    public static function accounts(): array
    {
        return [
            ['code' => '1000', 'name' => 'Assets', 'type' => AccountType::Asset, 'group' => 'Assets', 'parent_code' => null, 'is_system' => true, 'description' => null],
            ['code' => '1100', 'name' => 'Cash', 'type' => AccountType::Asset, 'group' => 'Assets', 'parent_code' => '1000', 'is_system' => true, 'description' => 'Cash in hand'],
            ['code' => '1200', 'name' => 'Bank', 'type' => AccountType::Asset, 'group' => 'Assets', 'parent_code' => '1000', 'is_system' => true, 'description' => 'Bank accounts'],
            ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => AccountType::Asset, 'group' => 'Assets', 'parent_code' => '1000', 'is_system' => true, 'description' => 'Customer outstanding'],
            ['code' => '1400', 'name' => 'Inventory', 'type' => AccountType::Asset, 'group' => 'Assets', 'parent_code' => '1000', 'is_system' => true, 'description' => 'Stock on hand'],
            ['code' => '1500', 'name' => 'GST Input', 'type' => AccountType::Asset, 'group' => 'Assets', 'parent_code' => '1000', 'is_system' => true, 'description' => 'Input GST credit'],

            ['code' => '2000', 'name' => 'Liabilities', 'type' => AccountType::Liability, 'group' => 'Liabilities', 'parent_code' => null, 'is_system' => true, 'description' => null],
            ['code' => '2100', 'name' => 'Accounts Payable', 'type' => AccountType::Liability, 'group' => 'Liabilities', 'parent_code' => '2000', 'is_system' => true, 'description' => 'Supplier outstanding'],
            ['code' => '2200', 'name' => 'GST Output', 'type' => AccountType::Liability, 'group' => 'Liabilities', 'parent_code' => '2000', 'is_system' => true, 'description' => 'Output GST payable'],
            ['code' => '2300', 'name' => 'Loans', 'type' => AccountType::Liability, 'group' => 'Liabilities', 'parent_code' => '2000', 'is_system' => true, 'description' => 'Borrowings'],

            ['code' => '3000', 'name' => 'Equity', 'type' => AccountType::Equity, 'group' => 'Equity', 'parent_code' => null, 'is_system' => true, 'description' => null],
            ['code' => '3100', 'name' => 'Capital', 'type' => AccountType::Equity, 'group' => 'Equity', 'parent_code' => '3000', 'is_system' => true, 'description' => 'Owner capital'],

            ['code' => '4000', 'name' => 'Income', 'type' => AccountType::Income, 'group' => 'Income', 'parent_code' => null, 'is_system' => true, 'description' => null],
            ['code' => '4100', 'name' => 'Sales', 'type' => AccountType::Income, 'group' => 'Income', 'parent_code' => '4000', 'is_system' => true, 'description' => 'Sales revenue'],

            ['code' => '5000', 'name' => 'Expenses', 'type' => AccountType::Expense, 'group' => 'Expenses', 'parent_code' => null, 'is_system' => true, 'description' => null],
            ['code' => '5100', 'name' => 'Purchase', 'type' => AccountType::Expense, 'group' => 'Expenses', 'parent_code' => '5000', 'is_system' => true, 'description' => 'Purchase expenses'],
            ['code' => '5200', 'name' => 'Cost of Goods Sold', 'type' => AccountType::Expense, 'group' => 'Expenses', 'parent_code' => '5000', 'is_system' => true, 'description' => 'COGS'],
            ['code' => '5300', 'name' => 'Rent', 'type' => AccountType::Expense, 'group' => 'Expenses', 'parent_code' => '5000', 'is_system' => true, 'description' => 'Rent expense'],
            ['code' => '5400', 'name' => 'Salary', 'type' => AccountType::Expense, 'group' => 'Expenses', 'parent_code' => '5000', 'is_system' => true, 'description' => 'Salary expense'],
            ['code' => '5500', 'name' => 'Utilities', 'type' => AccountType::Expense, 'group' => 'Expenses', 'parent_code' => '5000', 'is_system' => true, 'description' => 'Utility expenses'],
        ];
    }
}
