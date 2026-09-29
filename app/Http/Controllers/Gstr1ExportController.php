<?php

namespace App\Http\Controllers;

use App\Application\Services\Gstr1ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Gstr1ExportController extends Controller
{
    public function __invoke(Request $request, Gstr1ExportService $gstr1ExportService): StreamedResponse
    {
        abort_if(business_context()->business() === null, 404);

        $financialYear = business_context()->financialYear();

        $from = Carbon::parse($request->query('from', $financialYear?->starts_on->toDateString() ?? now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->query('to', now()->toDateString()));

        $business = business_context()->business();
        $filename = $gstr1ExportService->filename($from, $to);
        $json = $gstr1ExportService->toJson($business, $from, $to);

        return response()->streamDownload(
            static function () use ($json): void {
                echo $json;
            },
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }
}
