<?php

namespace App\Livewire\Masters;

use App\Application\Services\ProductService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\ProductCategory;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CategoriesIndex extends Component
{
    use DispatchesToasts;

    public string $name = '';

    public string $description = '';

    public function saveCategory(ProductService $productService): void
    {
        $this->authorize('manage_categories');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $productService->createCategory(business_context()->business(), $validated);

        $this->reset(['name', 'description']);
        $this->toastSuccess(__('Category created successfully.'));
    }

    public function render(): View
    {
        $this->authorize('manage_categories');

        return view('livewire.masters.categories-index', [
            'categories' => ProductCategory::query()->orderBy('name')->get(),
        ]);
    }
}
