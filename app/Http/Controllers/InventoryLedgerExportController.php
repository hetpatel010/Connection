<?php

namespace App\Http\Controllers;

use App\Application\Services\ExcelExportWriter;
use App\Application\Services\InventoryService;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventoryLedgerExportController extends Controller
{
    public function __invoke(
        Request $request,
        Product $product,
        InventoryService $inventoryService,
        ExcelExportWriter $writer,
    ): BinaryFileResponse|Response {
        abort_unless(business_context()->userCan('manage_inventory'), 403);

        $filters = [
            'from' => $request->string('from')->toString(),
            'to' => $request->string('to')->toString(),
            'type' => $request->string('type')->toString(),
            'reference' => $request->string('ref')->toString(),
            'search' => $request->string('q')->toString(),
        ];

        $table = $this->table($inventoryService->ledgerRows($product, $filters));

        $path = $writer->tempPath('stock_ledger');

        try {
            $writer->createExcel($path, [
                'sheetName' => $this->sheetName($product),
                'columns' => $table['columns'],
                'rows' => $table['rows'],
            ]);
        } catch (\Throwable $e) {
            $writer->cleanup($path);
            throw $e;
        }

        return response()->download($path, $this->filename($product))
            ->deleteFileAfterSend(true);
    }

    /**
     * @return array{columns: list<array{header: string, key: string, type: string}>, rows: list<array<string, mixed>>}
     */
    private function table(Collection $movements): array
    {
        $rows = [];

        /** @var StockMovement $movement */
        foreach ($movements as $movement) {
            $rows[] = [
                'date' => $movement->movement_date,
                'reference' => $movement->reference ?? '',
                'transaction_type' => $movement->ledgerLabel(),
                'description' => $movement->notes ?? '',
                'stock_in' => $movement->isIncoming() ? (float) $movement->quantity : 0,
                'stock_out' => $movement->isIncoming() ? 0 : (float) $movement->quantity,
                'balance' => (float) $movement->balance,
            ];
        }

        return [
            'columns' => [
                ['header' => 'Date', 'key' => 'date', 'type' => ExcelExportWriter::TYPE_DATE],
                ['header' => 'Reference No', 'key' => 'reference', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Transaction Type', 'key' => 'transaction_type', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Description', 'key' => 'description', 'type' => ExcelExportWriter::TYPE_TEXT],
                ['header' => 'Stock In', 'key' => 'stock_in', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Stock Out', 'key' => 'stock_out', 'type' => ExcelExportWriter::TYPE_NUMBER],
                ['header' => 'Balance', 'key' => 'balance', 'type' => ExcelExportWriter::TYPE_NUMBER],
            ],
            'rows' => $rows,
        ];
    }

    private function sheetName(Product $product): string
    {
        $name = trim($product->name) !== '' ? $product->name : 'Stock Ledger';

        return 'Ledger - '.$name;
    }

    private function filename(Product $product): string
    {
        $business = business_context()->business();
        $slug = $business !== null ? preg_replace('/[^a-z0-9]+/i', '-', strtolower($business->slug)) : 'business';
        $sku = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($product->sku ?? 'product'));

        return sprintf('%s-%s-stock-ledger-%s.xlsx', $slug, $sku, now()->format('Y-m-d'));
    }
}
