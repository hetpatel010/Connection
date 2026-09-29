<?php

namespace App\Livewire\Purchases;

use App\Application\Services\InvoiceTemplateService;
use App\Application\Services\PurchaseBillService;
use App\Domain\Transactions\Enums\BillingType;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Account;
use App\Models\Party;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreatePurchaseBill extends Component
{
    use DispatchesToasts;

    public string $billDate = '';

    public string $partyId = '';

    public string $settlementMode = 'credit';

    public string $paymentAccountId = '';

    public string $notes = '';

    public string $billingType = 'gst';

    public string $documentTemplate = '';

    /** @var array<int, array{product_id: string, description: string, quantity: string, unit_price: string, tax_rate: string}> */
    public array $lines = [];

    public function mount(): void
    {
        $this->authorize('create', PurchaseBill::class);
        $this->billDate = now()->toDateString();
        $this->lines = [
            ['product_id' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '', 'tax_rate' => '0'],
        ];
    }

    public function addLine(): void
    {
        $this->lines[] = ['product_id' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '', 'tax_rate' => '0'];
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
            return;
        }

        $product = Product::query()->find($productId);

        if ($product === null) {
            return;
        }

        $this->lines[$index]['description'] = $product->name;
        $this->lines[$index]['unit_price'] = (string) Money::paiseToRupees($product->purchase_price);

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

    public function saveDraft(PurchaseBillService $purchaseBillService): void
    {
        $this->authorize('create', PurchaseBill::class);

        [$attributes, $linePayload] = $this->validatedPayload();

        $bill = $purchaseBillService->createDraft(
            business: business_context()->business(),
            financialYear: business_context()->financialYear(),
            user: auth()->user(),
            attributes: $attributes,
            lines: $linePayload,
        );

        $this->toastSuccess(__('Draft bill :number saved.', ['number' => $bill->bill_number]));

        $this->redirectRoute('purchases.bills.index', navigate: true);
    }

    public function confirm(PurchaseBillService $purchaseBillService): void
    {
        $this->authorize('create', PurchaseBill::class);

        [$attributes, $linePayload] = $this->validatedPayload();

        $bill = $purchaseBillService->createDraft(
            business: business_context()->business(),
            financialYear: business_context()->financialYear(),
            user: auth()->user(),
            attributes: $attributes,
            lines: $linePayload,
        );

        $bill = $purchaseBillService->confirm($bill, auth()->user());

        $this->toastSuccess(__('Bill :number confirmed.', ['number' => $bill->bill_number]));

        $this->redirectRoute('purchases.bills.index', navigate: true);
    }

    public function render(InvoiceTemplateService $invoiceTemplateService): View
    {
        $billingType = BillingType::from($this->billingType);

        return view('livewire.purchases.purchase-bill-form', [
            'suppliers' => Party::query()->suppliers()->orderBy('name')->get(),
            'products' => Product::query()->orderBy('name')->get(),
            'paymentAccounts' => Account::query()->whereIn('code', ['1100', '1200'])->orderBy('code')->get(),
            'templateOptions' => $invoiceTemplateService->optionsFor($billingType),
            'editing' => false,
            'documentNumber' => null,
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: list<array<string, mixed>>}
     */
    private function validatedPayload(): array
    {
        $validated = $this->validate([
            'billDate' => ['required', 'date'],
            'partyId' => ['required', 'uuid'],
            'settlementMode' => ['required', 'in:credit,cash'],
            'paymentAccountId' => ['required_if:settlementMode,cash', 'nullable', 'uuid'],
            'billingType' => ['required', 'in:gst,non_gst'],
            'documentTemplate' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'uuid'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $linePayload = collect($validated['lines'])->map(fn (array $line): array => [
            'product_id' => $line['product_id'] ?: null,
            'description' => $line['description'],
            'quantity' => $line['quantity'],
            'unit_price' => Money::rupeesToPaise($line['unit_price']),
            'tax_rate' => $validated['billingType'] === 'gst' ? ($line['tax_rate'] ?: 0) : 0,
        ])->all();

        $attributes = [
            'party_id' => $validated['partyId'],
            'bill_date' => $validated['billDate'],
            'settlement_mode' => SettlementMode::from($validated['settlementMode']),
            'payment_account_id' => $validated['paymentAccountId'] ?: null,
            'billing_type' => BillingType::from($validated['billingType']),
            'document_template' => $validated['documentTemplate'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        return [$attributes, $linePayload];
    }
}
