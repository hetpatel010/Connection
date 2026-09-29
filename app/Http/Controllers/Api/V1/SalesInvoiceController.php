<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithJsonEnvelope;
use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    use RespondsWithJsonEnvelope;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SalesInvoice::class);

        $invoices = SalesInvoice::query()
            ->with(['party'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = (string) $request->string('search');

                $query->where(function ($builder) use ($search): void {
                    $builder->where('invoice_number', 'like', '%'.$search.'%')
                        ->orWhere('reference', 'like', '%'.$search.'%')
                        ->orWhereHas('party', fn ($partyQuery) => $partyQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success(
            $invoices->getCollection()->map(fn (SalesInvoice $invoice): array => $this->transform($invoice))->values(),
            [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
        );
    }

    public function show(SalesInvoice $salesInvoice): JsonResponse
    {
        $this->authorize('view', $salesInvoice);

        $salesInvoice->load(['party', 'lines']);

        return $this->success($this->transform($salesInvoice, includeLines: true));
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(SalesInvoice $invoice, bool $includeLines = false): array
    {
        $data = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'status' => $invoice->status->value,
            'settlement_mode' => $invoice->settlement_mode->value,
            'party' => $invoice->party ? [
                'id' => $invoice->party->id,
                'name' => $invoice->party->name,
            ] : null,
            'subtotal_amount' => $invoice->subtotal_amount,
            'tax_amount' => $invoice->tax_amount,
            'total_amount' => $invoice->total_amount,
            'reference' => $invoice->reference,
            'notes' => $invoice->notes,
            'confirmed_at' => $invoice->confirmed_at?->toIso8601String(),
            'created_at' => $invoice->created_at?->toIso8601String(),
            'updated_at' => $invoice->updated_at?->toIso8601String(),
        ];

        if ($includeLines) {
            $data['lines'] = $invoice->lines->map(fn ($line): array => [
                'id' => $line->id,
                'line_number' => $line->line_number,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'tax_rate' => $line->tax_rate,
                'tax_amount' => $line->tax_amount,
                'line_total' => $line->line_total,
            ])->values();
        }

        return $data;
    }
}
