<?php

namespace App\Http\Controllers\Masters;

use App\Application\Services\ProductService;
use App\Domain\Shared\Exceptions\ProductInUseException;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function destroy(Product $product, ProductService $productService): RedirectResponse
    {
        $this->authorize('delete', $product);

        try {
            $productService->delete($product);
        } catch (ProductInUseException) {
            return redirect()->route('masters.products.index')
                ->with('error', __('This product cannot be deleted because it is used in invoices, purchases or stock movements.'));
        }

        return redirect()->route('masters.products.index')
            ->with('status', __('Product deleted successfully.'));
    }
}
