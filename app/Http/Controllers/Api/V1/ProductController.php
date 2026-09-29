<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\RespondsWithJsonEnvelope;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use RespondsWithJsonEnvelope;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['category', 'unit'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = (string) $request->string('search');

                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('sku', 'like', '%'.$search.'%')
                        ->orWhere('barcode', 'like', '%'.$search.'%')
                        ->orWhere('hsn_sac', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->success(
            $products->getCollection()->map(fn (Product $product): array => $this->transform($product))->values(),
            [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        );
    }

    public function show(Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        $product->load(['category', 'unit']);

        return $this->success($this->transform($product));
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'hsn_sac' => $product->hsn_sac,
            'brand' => $product->brand,
            'category' => $product->category?->name,
            'unit' => $product->unit?->name,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'tax_rate' => $product->tax_rate,
            'tax_inclusive' => $product->tax_inclusive,
            'minimum_stock' => $product->minimum_stock,
            'opening_stock' => $product->opening_stock,
            'status' => $product->status->value,
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }
}
