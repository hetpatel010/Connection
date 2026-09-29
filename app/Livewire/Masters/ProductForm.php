<?php

namespace App\Livewire\Masters;

use App\Application\Services\ProductService;
use App\Domain\Products\GstRate;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProductForm extends Component
{
    use DispatchesToasts;

    public ?Product $product = null;

    public string $name = '';

    public string $sku = '';

    public string $barcode = '';

    public string $hsnSac = '';

    public ?string $categoryId = null;

    public ?string $unitId = null;

    public string $brand = '';

    public string $purchasePrice = '';

    public string $sellingPrice = '';

    public string $taxRate = '0.00';

    public bool $taxInclusive = false;

    public string $minimumStock = '0';

    public string $description = '';

    public function mount(?Product $product = null): void
    {
        if ($product !== null && $product->exists) {
            $this->authorize('update', $product);
            $this->product = $product;
            $this->fillFromProduct($product);
        } else {
            $this->authorize('create', Product::class);
            $this->unitId = Unit::query()->where('is_default', true)->value('id');
        }
    }

    public function save(ProductService $productService): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'hsnSac' => ['nullable', 'string', 'max:20'],
            'categoryId' => ['nullable', 'uuid'],
            'unitId' => ['required', 'uuid'],
            'brand' => ['nullable', 'string', 'max:100'],
            'purchasePrice' => ['nullable', 'numeric', 'min:0'],
            'sellingPrice' => ['required', 'numeric', 'min:0.01'],
            'taxRate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'taxInclusive' => ['boolean'],
            'minimumStock' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'sku' => $validated['sku'] ?: null,
            'barcode' => $validated['barcode'] ?: null,
            'hsn_sac' => $validated['hsnSac'] ?: null,
            'category_id' => $validated['categoryId'] ?: null,
            'unit_id' => $validated['unitId'],
            'brand' => $validated['brand'] ?: null,
            'purchase_price' => $validated['purchasePrice'] ?: 0,
            'selling_price' => $validated['sellingPrice'],
            'tax_rate' => $validated['taxRate'],
            'tax_inclusive' => $validated['taxInclusive'],
            'minimum_stock' => $validated['minimumStock'] ?: 0,
            'description' => $validated['description'] ?: null,
        ];

        if ($this->product !== null) {
            $productService->update($this->product, $payload);
            $this->toastSuccess(__('Product updated successfully.'));
        } else {
            $productService->create(business_context()->business(), $payload);
            $this->toastSuccess(__('Product created successfully.'));
        }

        $this->redirectRoute('masters.products.index', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.masters.product-form', [
            'categories' => ProductCategory::query()->orderBy('name')->get(),
            'units' => Unit::query()->orderBy('name')->get(),
            'gstRates' => $this->gstRateOptions(),
        ]);
    }

    /**
     * The GST slabs offered by the form, always including the rate the product
     * already carries so existing products outside the common slabs keep working.
     *
     * @return list<array{value: string, label: string}>
     */
    private function gstRateOptions(): array
    {
        $values = [];

        foreach (GstRate::slabs() as $slab) {
            $values[$this->formatRate($slab)] = true;
        }

        if (GstRate::isValid($this->taxRate)) {
            $values[$this->formatRate((float) $this->taxRate)] = true;
        }

        $options = [];

        foreach (array_keys($values) as $value) {
            $options[] = ['value' => $value, 'label' => $this->formatRateLabel($value)];
        }

        return $options;
    }

    private function formatRate(float $rate): string
    {
        return number_format($rate, 2, '.', '');
    }

    /**
     * Render 18.00 as "18%" while keeping 2.50 as "2.5%".
     */
    private function formatRateLabel(string $value): string
    {
        return rtrim(rtrim($value, '0'), '.').'%';
    }

    private function fillFromProduct(Product $product): void
    {
        $this->name = $product->name;
        $this->sku = $product->sku ?? '';
        $this->barcode = $product->barcode ?? '';
        $this->hsnSac = $product->hsn_sac ?? '';
        $this->categoryId = $product->category_id;
        $this->unitId = $product->unit_id;
        $this->brand = $product->brand ?? '';
        $this->purchasePrice = (string) Money::paiseToRupees($product->purchase_price);
        $this->sellingPrice = (string) Money::paiseToRupees($product->selling_price);
        $this->taxRate = (string) $product->tax_rate;
        $this->taxInclusive = $product->tax_inclusive;
        $this->minimumStock = (string) $product->minimum_stock;
        $this->description = $product->description ?? '';
    }
}
