<?php

namespace App\Livewire\Inventory;

use App\Application\Services\InventoryService;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class StockLedger extends Component
{
    use WithPagination;

    public Product $product;

    #[Url(as: 'from', except: '')]
    public string $fromDate = '';

    #[Url(as: 'to', except: '')]
    public string $toDate = '';

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(as: 'ref', except: '')]
    public string $reference = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $perPage = 25;

    public function mount(Product $product): void
    {
        abort_unless(business_context()->userCan('manage_inventory'), 403);
        $this->product = $product;
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->fromDate = '';
        $this->toDate = '';
        $this->type = '';
        $this->reference = '';
        $this->search = '';
        $this->resetPage();
    }

    public function updatingFromDate(): void
    {
        $this->resetPage();
    }

    public function updatingToDate(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingReference(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function documentAction(StockMovement $movement): ?string
    {
        $sourceType = $movement->source_type;
        $sourceId = $movement->source_id;

        if ($sourceId === null) {
            return null;
        }

        return match ($sourceType) {
            'sales_invoice' => business_context()->userCan('edit_invoice')
                ? route('sales.invoices.edit', $sourceId)
                : null,
            'purchase_bill' => business_context()->userCan('edit_purchase')
                ? route('purchases.bills.edit', $sourceId)
                : null,
            'credit_note' => business_context()->userCan('view_invoice')
                ? route('sales.credit-notes.index')
                : null,
            'debit_note' => business_context()->userCan('view_purchase')
                ? route('purchases.debit-notes.index')
                : null,
            default => null,
        };
    }

    public function render(InventoryService $inventoryService): View
    {
        $filters = [
            'from' => $this->fromDate,
            'to' => $this->toDate,
            'type' => $this->type,
            'reference' => $this->reference,
            'search' => $this->search,
        ];

        $movements = $inventoryService->movementQuery($this->product, $filters)
            ->paginate($this->perPage)
            ->withQueryString();

        $this->attachBalances($movements, $inventoryService);

        return view('livewire.inventory.stock-ledger', [
            'movements' => $movements,
            'totals' => $inventoryService->stockTotals($this->product),
            'currentStock' => $inventoryService->currentStock($this->product),
            'hasAnyMovements' => $inventoryService->movementQuery($this->product)->exists(),
            'typeOptions' => StockMovementType::filterOptions(),
        ]);
    }

    private function attachBalances(LengthAwarePaginator $paginator, InventoryService $inventoryService): void
    {
        $balances = $inventoryService->runningBalances($this->product);

        foreach ($paginator->items() as $movement) {
            $movement->setAttribute('balance', $balances[$movement->id] ?? 0.0);
        }
    }
}
