<?php

namespace App\Application\Services;

use App\Models\Business;
use App\Models\PurchaseBill;

class PurchaseBillDocumentService
{
    public function __construct(
        private InvoiceDocumentService $invoiceDocumentService,
        private InvoiceTemplateService $invoiceTemplateService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function viewData(PurchaseBill $bill, Business $business, bool $showToolbar = false): array
    {
        $bill->loadMissing(['party', 'lines']);

        $invoiceView = DocumentViewAdapter::purchaseBillAsInvoice($bill);

        return array_merge(
            $this->invoiceDocumentService->viewData(
                invoice: $invoiceView,
                business: $business,
                showToolbar: $showToolbar,
                documentContext: 'purchase',
                isPurchaseDocument: true,
            ),
            ['purchaseBill' => $bill],
        );
    }

    public function render(PurchaseBill $bill, Business $business): string
    {
        return view('sales.invoices.document', $this->viewData($bill, $business))->render();
    }

    public function downloadFilename(PurchaseBill $bill): string
    {
        return $bill->bill_number.'.html';
    }
}
