<?php

namespace App\Livewire\Inventory;

use App\Application\Services\InventoryService;
use App\Domain\Inventory\Enums\StockDirection;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdjustStock extends Component
{
    use DispatchesToasts;

    public Product $product;

    public string $movementDate = '';

    public string $direction = 'in';

    public string $quantity = '';

    public string $reference = '';

    public string $notes = '';

    public function mount(Product $product): void
    {
        abort_unless(business_context()->userCan('manage_inventory'), 403);
        $this->product = $product;
        $this->movementDate = now()->toDateString();
    }

    public function save(InventoryService $inventoryService): void
    {
        abort_unless(business_context()->userCan('manage_inventory'), 403);

        $validated = $this->validate([
            'movementDate' => ['required', 'date'],
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $inventoryService->adjust(
            product: $this->product,
            user: auth()->user(),
            quantity: (float) $validated['quantity'],
            direction: StockDirection::from($validated['direction']),
            movementDate: $validated['movementDate'],
            notes: $validated['notes'] ?: null,
            reference: $validated['reference'] ?: null,
        );

        $this->toastSuccess(__('Stock adjusted successfully.'));

        $this->redirectRoute('inventory.ledger', $this->product, navigate: true);
    }

    public function render(InventoryService $inventoryService): View
    {
        return view('livewire.inventory.adjust-stock', [
            'currentStock' => $inventoryService->currentStock($this->product),
        ]);
    }
}
