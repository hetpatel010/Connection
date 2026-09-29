<?php

namespace App\Livewire\Inventory;

use App\Application\Services\InventoryService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InventoryIndex extends Component
{
    public string $search = '';

    public function render(InventoryService $inventoryService): View
    {
        abort_unless(business_context()->userCan('manage_inventory'), 403);

        $levels = $inventoryService->stockLevels(
            business_context()->business(),
            $this->search !== '' ? $this->search : null,
        );

        $lowStockCount = $levels->where('is_low', true)->count();

        return view('livewire.inventory.inventory-index', [
            'levels' => $levels,
            'lowStockCount' => $lowStockCount,
        ]);
    }
}
