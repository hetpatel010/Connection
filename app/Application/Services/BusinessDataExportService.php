<?php

namespace App\Application\Services;

use App\Models\Account;
use App\Models\Business;
use App\Models\Expense;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use Illuminate\Support\Carbon;
use ZipArchive;

class BusinessDataExportService
{
    public function __construct(
        private GstReportService $gstReportService,
    ) {}

    public function buildArchive(Business $business, FinancialYear $financialYear): string
    {
        $exportedAt = now();
        $tempDir = storage_path('app/temp/exports/'.uniqid('export_', true));
        mkdir($tempDir, 0755, true);

        $tables = [
            'business' => $business->toArray(),
            'financial_year' => $financialYear->toArray(),
            'parties' => Party::query()->where('business_id', $business->id)->get()->toArray(),
            'products' => Product::query()->where('business_id', $business->id)->get()->toArray(),
            'sales_invoices' => SalesInvoice::query()->where('business_id', $business->id)->with('lines')->get()->toArray(),
            'purchase_bills' => PurchaseBill::query()->where('business_id', $business->id)->with('lines')->get()->toArray(),
            'payments' => Payment::query()->where('business_id', $business->id)->with('allocations')->get()->toArray(),
            'expenses' => Expense::query()->where('business_id', $business->id)->get()->toArray(),
            'accounts' => Account::query()->where('business_id', $business->id)->get()->toArray(),
        ];

        $manifest = [
            'exported_at' => $exportedAt->toIso8601String(),
            'app' => branding('product.name', config('app.name')),
            'version' => branding('product.version') ?: app()->version(),
            'business_id' => $business->id,
            'business_name' => $business->name,
            'financial_year' => $financialYear->label,
            'tables' => array_keys($tables),
        ];

        file_put_contents($tempDir.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        foreach ($tables as $name => $data) {
            file_put_contents(
                $tempDir.'/'.$name.'.json',
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }

        $monthStart = Carbon::now()->startOfMonth();
        $gstSummary = $this->gstReportService->generate($business, $monthStart, Carbon::now());
        file_put_contents(
            $tempDir.'/gst_summary_current_month.json',
            json_encode($gstSummary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        $zipPath = $tempDir.'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create export archive.');
        }

        foreach (glob($tempDir.'/*') as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        foreach (glob($tempDir.'/*') as $file) {
            unlink($file);
        }

        rmdir($tempDir);

        return $zipPath;
    }

    public function archiveFilename(Business $business, FinancialYear $financialYear): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($business->slug));

        return sprintf('%s-fy-%s-export.zip', $slug, str_replace('/', '-', $financialYear->label));
    }

    public function cleanup(string $zipPath): void
    {
        if (is_file($zipPath)) {
            unlink($zipPath);
        }
    }
}
