<?php

namespace App\Application\Services;

use App\Domain\Invoicing\InvoiceTemplateRegistry;
use App\Domain\Transactions\Enums\BillingType;
use App\Models\Business;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;

class InvoiceTemplateService
{
    public function defaultTemplate(Business $business, BillingType $billingType): string
    {
        $settings = $business->settings ?? [];
        $key = $billingType->isGst()
            ? 'invoice.default_template_gst'
            : 'invoice.default_template_non_gst';

        $template = $settings[$key] ?? null;

        if (is_string($template) && $this->isValid($template, $billingType)) {
            return $template;
        }

        return InvoiceTemplateRegistry::defaultFor($billingType);
    }

    public function setDefaultTemplate(Business $business, BillingType $billingType, string $template): Business
    {
        abort_unless($this->isValid($template, $billingType), 422, 'Invalid template for this billing type.');

        $settings = $business->settings ?? [];
        $key = $billingType->isGst()
            ? 'invoice.default_template_gst'
            : 'invoice.default_template_non_gst';
        $settings[$key] = $template;

        $business->update(['settings' => $settings]);

        return $business->fresh();
    }

    /**
     * Resolve the template to pin on a newly created document.
     *
     * A document created without an explicit template takes the business default,
     * so the choice saved on the invoice templates page is captured at creation
     * time. Pinning it is what makes that default a permanent business-level
     * default: a later change only reaches documents created afterwards and
     * never rewrites the template of a document that already exists.
     */
    public function resolveForNewDocument(Business $business, BillingType $billingType, ?string $template = null): string
    {
        if (is_string($template) && $this->isValid($template, $billingType)) {
            return $template;
        }

        return $this->defaultTemplate($business, $billingType);
    }

    public function resolveForInvoice(SalesInvoice $invoice, Business $business): string
    {
        $billingType = $invoice->billing_type ?? BillingType::Gst;

        if ($invoice->document_template !== null && $this->isValid($invoice->document_template, $billingType)) {
            return $invoice->document_template;
        }

        return $this->defaultTemplate($business, $billingType);
    }

    public function resolveForPurchaseBill(PurchaseBill $bill, Business $business): string
    {
        $billingType = $bill->billing_type ?? BillingType::Gst;

        if ($bill->document_template !== null && $this->isValid($bill->document_template, $billingType)) {
            return $bill->document_template;
        }

        return $this->defaultTemplate($business, $billingType);
    }

    public function isValid(string $template, BillingType $billingType): bool
    {
        if (! in_array($template, InvoiceTemplateRegistry::slugs(), true)) {
            return false;
        }

        return InvoiceTemplateRegistry::isCompatible($template, $billingType);
    }

    /**
     * @return list<array{slug: string, name: string, description: string}>
     */
    public function optionsFor(BillingType $billingType): array
    {
        return collect(InvoiceTemplateRegistry::definitions())
            ->filter(fn (array $definition): bool => $definition['billing_type'] === $billingType)
            ->map(fn (array $definition, string $slug): array => [
                'slug' => $slug,
                'name' => $definition['name'],
                'description' => $definition['description'],
            ])
            ->values()
            ->all();
    }
}
