<?php

namespace App\Livewire\Sales;

use App\Application\Services\InvoiceTemplateService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Transactions\Enums\BillingType;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Domain\Transactions\LineTotals;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Account;
use App\Models\Party;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Unit;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class EditInvoice extends Component
{
    use DispatchesToasts;

    public SalesInvoice $invoice;

    public string $invoiceDate = '';

    public string $partyId = '';

    public string $settlementMode = 'credit';

    public string $paymentAccountId = '';

    public string $notes = '';

    public string $billingType = 'gst';

    public string $documentTemplate = '';

    /** @var array<int, array{product_id: string, unit_id: string, description: string, quantity: string, unit_price: string, tax_rate: string}> */
    public array $lines = [];

    public function mount(SalesInvoice $invoice): void
    {
        $this->authorize('update', $invoice);

        $this->invoice = $invoice->load('lines');
        $this->invoiceDate = $invoice->invoice_date->toDateString();
        $this->partyId = $invoice->party_id;
        $this->settlementMode = $invoice->settlement_mode->value;
        $this->paymentAccountId = $invoice->payment_account_id ?? '';
        $this->notes = $invoice->notes ?? '';
        $this->billingType = $invoice->billing_type->value;
        $this->documentTemplate = $invoice->document_template ?? '';
        $this->lines = $invoice->lines->map(fn ($line): array => [
            'product_id' => $line->product_id ?? '',
            'unit_id' => $line->unit_id ?? '',
            'description' => $line->description,
            'quantity' => (string) $line->quantity,
            'unit_price' => (string) Money::paiseToRupees($line->unit_price),
            'tax_rate' => (string) $line->tax_rate,
        ])->all();

        if ($this->lines === []) {
            $this->lines = [
                ['product_id' => '', 'unit_id' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '', 'tax_rate' => '0'],
            ];
        }
    }

    public function addLine(): void
    {
        $this->lines[] = ['product_id' => '', 'unit_id' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '', 'tax_rate' => '0'];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) <= 1) {
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function updatedLines(mixed $value, string $key): void
    {
        if (! str_ends_with($key, 'product_id')) {
            return;
        }

        $index = (int) explode('.', $key)[0];
        $productId = $this->lines[$index]['product_id'] ?? '';

        if ($productId === '') {
            $this->lines[$index]['unit_id'] = '';

            return;
        }

        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        $this->lines[$index]['description'] = $product->name;
        $this->lines[$index]['unit_id'] = $product->unit_id;
        $this->lines[$index]['unit_price'] = (string) Money::paiseToRupees($product->selling_price);

        if ($this->billingType === 'gst') {
            $this->lines[$index]['tax_rate'] = (string) $product->tax_rate;
        }
    }

    public function updatedBillingType(): void
    {
        if ($this->billingType !== 'non_gst') {
            return;
        }

        $this->documentTemplate = '';

        foreach ($this->lines as $index => $line) {
            $this->lines[$index]['tax_rate'] = '0';
        }
    }

    public function saveDraft(SalesInvoiceService $salesInvoiceService): void
    {
        $this->authorize('update', $this->invoice);

        [$attributes, $linePayload] = $this->validatedPayload();

        $invoice = $salesInvoiceService->updateDraft(
            invoice: $this->invoice,
            user: auth()->user(),
            attributes: $attributes,
            lines: $linePayload,
        );

        $this->toastSuccess(__('Draft invoice :number updated.', ['number' => $invoice->invoice_number]));

        $this->redirectRoute('sales.invoices.index', navigate: true);
    }

    public function confirm(SalesInvoiceService $salesInvoiceService): void
    {
        $this->authorize('confirm', $this->invoice);

        [$attributes, $linePayload] = $this->validatedPayload();

        $invoice = $salesInvoiceService->updateDraft(
            invoice: $this->invoice,
            user: auth()->user(),
            attributes: $attributes,
            lines: $linePayload,
        );

        $invoice = $salesInvoiceService->confirm($invoice, auth()->user());

        $this->toastSuccess(__('Invoice :number confirmed.', ['number' => $invoice->invoice_number]));

        $this->redirectRoute('sales.invoices.index', navigate: true);
    }

    public function deleteDraft(SalesInvoiceService $salesInvoiceService): void
    {
        $this->authorize('delete', $this->invoice);

        $number = $this->invoice->invoice_number;
        $salesInvoiceService->deleteDraft($this->invoice, auth()->user());

        $this->toastSuccess(__('Draft invoice :number deleted.', ['number' => $number]));

        $this->redirectRoute('sales.invoices.index', navigate: true);
    }

    public function render(InvoiceTemplateService $invoiceTemplateService): View
    {
        $billingType = BillingType::from($this->billingType);

        $products = Product::query()->orderBy('name')->get();

        return view('livewire.sales.invoice-form', [
            'customers' => Party::query()->customers()->orderBy('name')->get(),
            'products' => $products,
            'productsById' => $products->keyBy('id'),
            'units' => Unit::query()->orderBy('name')->get(),
            'paymentAccounts' => Account::query()->whereIn('code', ['1100', '1200'])->orderBy('code')->get(),
            'templateOptions' => $invoiceTemplateService->optionsFor($billingType),
            'totals' => $this->previewTotals($billingType),
            'editing' => true,
            'documentNumber' => $this->invoice->invoice_number,
        ]);
    }

    /**
     * Totals shown on the form, produced by the same domain calculation and the
     * same per-line rounding that will be persisted on save.
     *
     * @return array{subtotal: int, tax: int, total: int}
     */
    private function previewTotals(BillingType $billingType): array
    {
        $preparedLines = [];

        foreach ($this->lines as $line) {
            $preparedLines[] = [
                'quantity' => $line['quantity'] ?? 1,
                'unit_price' => Money::rupeesToPaise($line['unit_price'] ?? 0),
                'tax_rate' => $billingType->isGst() ? ($line['tax_rate'] ?? 0) : 0,
            ];
        }

        $totals = LineTotals::fromLines($preparedLines);

        return [
            'subtotal' => $totals['subtotal'],
            'tax' => $totals['tax'],
            'total' => $totals['total'],
        ];
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function validatedPayload(): array
    {
        $validated = $this->validate([
            'invoiceDate' => ['required', 'date'],
            'partyId' => ['required', 'uuid'],
            'settlementMode' => ['required', 'in:credit,cash'],
            'paymentAccountId' => ['required_if:settlementMode,cash', 'nullable', 'uuid'],
            'billingType' => ['required', 'in:gst,non_gst'],
            'documentTemplate' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'uuid'],
            'lines.*.unit_id' => ['nullable', 'uuid'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
        ]);

        $linePayload = collect($validated['lines'])->map(fn (array $line): array => [
            'product_id' => $line['product_id'] ?: null,
            'unit_id' => $line['unit_id'] ?: null,
            'description' => $line['description'],
            'quantity' => $line['quantity'],
            'unit_price' => Money::rupeesToPaise($line['unit_price']),
            'tax_rate' => $validated['billingType'] === 'gst' ? ($line['tax_rate'] ?: 0) : 0,
        ])->all();

        $attributes = [
            'party_id' => $validated['partyId'],
            'invoice_date' => $validated['invoiceDate'],
            'settlement_mode' => SettlementMode::from($validated['settlementMode']),
            'payment_account_id' => $validated['paymentAccountId'] ?: null,
            'billing_type' => BillingType::from($validated['billingType']),
            'document_template' => $validated['documentTemplate'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        return [$attributes, $linePayload];
    }
}
