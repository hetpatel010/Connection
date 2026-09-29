<?php

namespace App\Domain\Exports;

use App\Domain\Authorization\Permissions;

enum ListExportModule: string
{
    case SalesInvoices = 'sales-invoices';

    case PurchaseBills = 'purchase-bills';

    case Customers = 'customers';

    case Suppliers = 'suppliers';

    case Products = 'products';

    case Categories = 'categories';

    case Units = 'units';

    case Payments = 'payments';

    case Expenses = 'expenses';

    case CreditNotes = 'credit-notes';

    case DebitNotes = 'debit-notes';

    case Inventory = 'inventory';

    case Accounts = 'accounts';

    public function permission(): string
    {
        return match ($this) {
            self::SalesInvoices, self::CreditNotes => Permissions::VIEW_INVOICE,
            self::PurchaseBills, self::DebitNotes => Permissions::VIEW_PURCHASE,
            self::Customers => Permissions::VIEW_CUSTOMERS,
            self::Suppliers => Permissions::VIEW_SUPPLIERS,
            self::Products => Permissions::VIEW_PRODUCTS,
            self::Categories => Permissions::MANAGE_CATEGORIES,
            self::Units => Permissions::MANAGE_UNITS,
            self::Payments => Permissions::VIEW_PAYMENT,
            self::Expenses => Permissions::VIEW_EXPENSE,
            self::Inventory => Permissions::MANAGE_INVENTORY,
            self::Accounts => Permissions::MANAGE_CHART_OF_ACCOUNTS,
        };
    }

    public function sheetName(): string
    {
        return match ($this) {
            self::SalesInvoices => 'Sales Invoices',
            self::PurchaseBills => 'Purchase Bills',
            self::Customers => 'Customers',
            self::Suppliers => 'Suppliers',
            self::Products => 'Products',
            self::Categories => 'Categories',
            self::Units => 'Units',
            self::Payments => 'Payments',
            self::Expenses => 'Expenses',
            self::CreditNotes => 'Credit Notes',
            self::DebitNotes => 'Debit Notes',
            self::Inventory => 'Inventory',
            self::Accounts => 'Accounts',
        };
    }

    public function filenamePrefix(): string
    {
        return str_replace('-', '_', $this->value);
    }

    public static function tryFromSlug(string $slug): ?self
    {
        return self::tryFrom($slug);
    }
}
