<?php

namespace App\Http\Controllers;

use App\Application\Services\ListExcelExportService;
use App\Domain\Exports\ListExportModule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListExcelExportController extends Controller
{
    public function __invoke(Request $request, string $module, ListExcelExportService $exportService): BinaryFileResponse|Response
    {
        $exportModule = ListExportModule::tryFromSlug($module);

        abort_if($exportModule === null, 404);

        $business = business_context()->business();

        abort_if($business === null, 404);

        abort_unless(business_context()->userCan($exportModule->permission()), 403);

        $path = $exportService->export($exportModule, $business, [
            'search' => $request->string('search')->toString(),
            'type' => $request->string('type')->toString(),
            'group' => $request->string('group')->toString(),
            'status' => $request->string('status')->toString(),
            'view' => $request->string('view')->toString(),
        ]);

        $filename = $exportService->filename($exportModule, $business);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
