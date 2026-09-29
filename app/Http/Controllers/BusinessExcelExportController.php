<?php

namespace App\Http\Controllers;

use App\Application\Services\BusinessExcelExportService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessExcelExportController extends Controller
{
    public function __invoke(BusinessExcelExportService $exportService): BinaryFileResponse|Response
    {
        $business = business_context()->business();
        $financialYear = business_context()->financialYear();

        abort_if($business === null || $financialYear === null, 404);

        $this->authorize('exportData', $business);

        $path = $exportService->buildWorkbook($business, $financialYear);
        $filename = $exportService->filename($business, $financialYear);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
