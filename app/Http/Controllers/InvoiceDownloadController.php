<?php

namespace App\Http\Controllers;

use App\Application\Services\InvoiceDocumentService;
use App\Models\SalesInvoice;
use Illuminate\Http\Response;

class InvoiceDownloadController extends Controller
{
    public function __invoke(SalesInvoice $invoice, InvoiceDocumentService $invoiceDocumentService): Response
    {
        abort_unless($invoice->status->isConfirmed(), 404);

        $business = business_context()->business();
        abort_if($business === null || $invoice->business_id !== $business->id, 404);

        $html = $invoiceDocumentService->render($invoice, $business);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$invoiceDocumentService->downloadFilename($invoice).'"',
        ]);
    }
}
