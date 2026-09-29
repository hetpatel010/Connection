<?php

namespace App\Http\Controllers;

use App\Application\Services\BusinessDataExportService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BusinessExportController extends Controller
{
    public function __invoke(BusinessDataExportService $exportService): BinaryFileResponse|Response
    {
        $business = business_context()->business();
        $financialYear = business_context()->financialYear();

        abort_if($business === null || $financialYear === null, 404);

        $this->authorize('exportData', $business);

        $zipPath = $exportService->buildArchive($business, $financialYear);
        $filename = $exportService->archiveFilename($business, $financialYear);

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }
}
