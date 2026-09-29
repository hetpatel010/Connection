<?php

namespace App\Application\Services;

use App\Domain\Products\GstRate;
use App\Domain\Shared\Enums\RecordStatus;
use App\Domain\Shared\Exceptions\ProductInUseException;
use App\Models\Business;
use App\Models\CreditNoteLine;
use App\Models\DebitNoteLine;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseBillLine;
use App\Models\SalesInvoiceLine;
use App\Models\StockMovement;
use App\Support\Money;

class ProductService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Business $business, array $attributes): Product
    {
        return Product::query()->create($this->mapAttributes($business, $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Product $product, array $attributes): Product
    {
        $product->update($this->mapAttributes($product->business, $attributes, $product));

        return $product->fresh(['category', 'unit']);
    }

    public function deactivate(Product $product): Product
    {
        $product->update(['status' => RecordStatus::Inactive]);

        return $product->fresh();
    }

    public function delete(Product $product): void
    {
        $this->assertDeletable($product);

        $product->delete();
    }

    public function assertDeletable(Product $product): void
    {
        if ($this->hasLinkedRecords($product)) {
            throw ProductInUseException::forProduct($product->name);
        }
    }

    private function hasLinkedRecords(Product $product): bool
    {
        return SalesInvoiceLine::query()->where('product_id', $product->id)->exists()
            || PurchaseBillLine::query()->where('product_id', $product->id)->exists()
            || CreditNoteLine::query()->where('product_id', $product->id)->exists()
            || DebitNoteLine::query()->where('product_id', $product->id)->exists()
            || StockMovement::query()->where('product_id', $product->id)->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createCategory(Business $business, array $attributes): ProductCategory
    {
        return ProductCategory::query()->create([
            'business_id' => $business->id,
            'parent_id' => $attributes['parent_id'] ?? null,
            'name' => $attributes['name'],
            'description' => $attributes['description'] ?? null,
            'status' => RecordStatus::Active,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function mapAttributes(Business $business, array $attributes, ?Product $existing = null): array
    {
        return [
            'business_id' => $business->id,
            'category_id' => $attributes['category_id'] ?? $existing?->category_id,
            'unit_id' => $attributes['unit_id'] ?? $existing?->unit_id,
            'name' => $attributes['name'],
            'sku' => $attributes['sku'] ?? null,
            'barcode' => $attributes['barcode'] ?? null,
            'hsn_sac' => $attributes['hsn_sac'] ?? null,
            'brand' => $attributes['brand'] ?? null,
            'purchase_price' => isset($attributes['purchase_price'])
                ? Money::rupeesToPaise($attributes['purchase_price'])
                : ($existing?->purchase_price ?? 0),
            'selling_price' => isset($attributes['selling_price'])
                ? Money::rupeesToPaise($attributes['selling_price'])
                : ($existing?->selling_price ?? 0),
            'tax_rate' => GstRate::normalize($attributes['tax_rate'] ?? $existing?->tax_rate ?? 0),
            'tax_inclusive' => $attributes['tax_inclusive'] ?? $existing?->tax_inclusive ?? false,
            'minimum_stock' => $attributes['minimum_stock'] ?? $existing?->minimum_stock ?? 0,
            'opening_stock' => $attributes['opening_stock'] ?? $existing?->opening_stock ?? 0,
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'] ?? RecordStatus::Active,
        ];
    }
}
