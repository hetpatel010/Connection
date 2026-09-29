<?php

namespace App\Application\Services;

use App\Domain\Invoicing\InvoiceTemplateRegistry;
use App\Domain\Transactions\Enums\BillingType;
use App\Models\Business;
use App\Models\SalesInvoice;
use App\Support\GstTaxBreakdown;
use App\Support\IndianAmountInWords;

class InvoiceDocumentService
{
    public function __construct(
        private InvoiceTemplateService $invoiceTemplateService,
    ) {}

    /**
     * @return array{
     *     invoice: SalesInvoice,
     *     business: Business,
     *     taxBreakdown: array{is_inter_state: bool, cgst: int, sgst: int, igst: int, total_tax: int, place_of_supply: string, hsn_summary: list<array<string, mixed>>},
     *     amountInWords: string,
     *     templateView: string,
     *     documentTitle: string,
     *     showToolbar?: bool,
     *     documentContext?: string,
     *     isPurchaseDocument?: bool
     * }
     */
    public function viewData(
        SalesInvoice $invoice,
        Business $business,
        bool $showToolbar = false,
        string $documentContext = 'sales',
        bool $isPurchaseDocument = false,
    ): array {
        $invoice->loadMissing(['party', 'lines']);

        $billingType = $invoice->billing_type ?? BillingType::Gst;
        $templateSlug = $this->invoiceTemplateService->resolveForInvoice($invoice, $business);

        return [
            'invoice' => $invoice,
            'business' => $business,
            'businessBranding' => app(BusinessBrandingService::class)->forBusiness($business),
            'taxBreakdown' => GstTaxBreakdown::forInvoice($invoice, $business),
            'amountInWords' => IndianAmountInWords::fromPaise((int) $invoice->total_amount),
            'templateView' => InvoiceTemplateRegistry::viewFor($templateSlug),
            'documentTitle' => $isPurchaseDocument
                ? ($billingType->isGst() ? __('Purchase Bill') : __('Purchase Bill (Non-GST)'))
                : $billingType->documentTitle(),
            'showToolbar' => $showToolbar,
            'documentContext' => $documentContext,
            'isPurchaseDocument' => $isPurchaseDocument,
        ];
    }

    public function render(SalesInvoice $invoice, Business $business): string
    {
        return view('sales.invoices.document', $this->viewData($invoice, $business))->render();
    }

    public function downloadFilename(SalesInvoice $invoice): string
    {
        return $invoice->invoice_number.'.html';
    }
}
