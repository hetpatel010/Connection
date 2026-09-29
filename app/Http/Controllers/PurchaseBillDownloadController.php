<?php

namespace App\Http\Controllers;

use App\Application\Services\PurchaseBillDocumentService;
use App\Models\PurchaseBill;
use Illuminate\Http\Response;

class PurchaseBillDownloadController extends Controller
{
    public function __invoke(PurchaseBill $bill, PurchaseBillDocumentService $purchaseBillDocumentService): Response
    {
        abort_unless($bill->status->isConfirmed(), 404);

        $business = business_context()->business();
        abort_if($business === null || $bill->business_id !== $business->id, 404);

        $html = $purchaseBillDocumentService->render($bill, $business);

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$purchaseBillDocumentService->downloadFilename($bill).'"',
        ]);
    }
}
