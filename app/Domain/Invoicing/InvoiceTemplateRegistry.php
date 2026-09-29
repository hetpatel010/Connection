<?php

namespace App\Domain\Invoicing;

use App\Domain\Transactions\Enums\BillingType;
use InvalidArgumentException;

final class InvoiceTemplateRegistry
{
    public const CLASSIC_GST = 'classic-gst';

    public const MINIMAL_GST = 'minimal-gst';

    public const MODERN_GST = 'modern-gst';

    public const CLASSIC_BILL = 'classic-bill';

    public const MINIMAL_BILL = 'minimal-bill';

    /**
     * @return array<string, array{name: string, description: string, billing_type: BillingType, view: string}>
     */
    public static function definitions(): array
    {
        return [
            self::CLASSIC_GST => [
                'name' => __('Classic GST'),
                'description' => __('Standard tax invoice with CGST/SGST/IGST breakdown and HSN columns.'),
                'billing_type' => BillingType::Gst,
                'view' => 'sales.invoices.templates.classic-gst',
            ],
            self::MINIMAL_GST => [
                'name' => __('Minimal GST'),
                'description' => __('Compact GST layout with essential tax details.'),
                'billing_type' => BillingType::Gst,
                'view' => 'sales.invoices.templates.minimal-gst',
            ],
            self::MODERN_GST => [
                'name' => __('Modern GST'),
                'description' => __('Contemporary GST invoice with accent header styling.'),
                'billing_type' => BillingType::Gst,
                'view' => 'sales.invoices.templates.modern-gst',
            ],
            self::CLASSIC_BILL => [
                'name' => __('Classic Bill'),
                'description' => __('Simple non-GST bill without tax columns or GSTIN blocks.'),
                'billing_type' => BillingType::NonGst,
                'view' => 'sales.invoices.templates.classic-bill',
            ],
            self::MINIMAL_BILL => [
                'name' => __('Minimal Bill'),
                'description' => __('Clean non-GST receipt-style layout.'),
                'billing_type' => BillingType::NonGst,
                'view' => 'sales.invoices.templates.minimal-bill',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * @return list<string>
     */
    public static function slugsFor(BillingType $billingType): array
    {
        return collect(self::definitions())
            ->filter(fn (array $definition): bool => $definition['billing_type'] === $billingType)
            ->keys()
            ->values()
            ->all();
    }

    public static function defaultFor(BillingType $billingType): string
    {
        return $billingType->isGst() ? self::CLASSIC_GST : self::CLASSIC_BILL;
    }

    public static function viewFor(string $slug): string
    {
        $definition = self::definitions()[$slug] ?? null;

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown invoice template [{$slug}].");
        }

        return $definition['view'];
    }

    public static function billingTypeFor(string $slug): BillingType
    {
        $definition = self::definitions()[$slug] ?? null;

        if ($definition === null) {
            throw new InvalidArgumentException("Unknown invoice template [{$slug}].");
        }

        return $definition['billing_type'];
    }

    public static function isCompatible(string $slug, BillingType $billingType): bool
    {
        return self::billingTypeFor($slug) === $billingType;
    }
}
