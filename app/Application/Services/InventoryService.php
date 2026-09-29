<?php

namespace App\Application\Services;

use App\Domain\Inventory\Enums\StockDirection;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Shared\Exceptions\InsufficientStockException;
use App\Models\Business;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Product;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function openingStock(Product $product): float
    {
        return (float) $product->opening_stock;
    }

    public function currentStock(Product $product): float
    {
        $movementDelta = StockMovement::query()
            ->where('product_id', $product->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE -quantity END), 0) as delta")
            ->value('delta');

        return $this->openingStock($product) + (float) $movementDelta;
    }

    public function lowStockCount(Business $business): int
    {
        return $this->stockLevels($business)
            ->filter(fn (array $row): bool => $row['is_low'])
            ->count();
    }

    /**
     * @return Collection<int, array{product: Product, current_stock: float, minimum_stock: float, is_low: bool}>
     */
    public function stockLevels(Business $business, ?string $search = null): Collection
    {
        $query = Product::query()
            ->with('unit')
            ->where('status', 'active')
            ->orderBy('name');

        if ($search !== null && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            });
        }

        return $query->get()->map(function (Product $product): array {
            $currentStock = $this->currentStock($product);

            return [
                'product' => $product,
                'current_stock' => $currentStock,
                'minimum_stock' => (float) $product->minimum_stock,
                'is_low' => $product->minimum_stock > 0 && $currentStock <= (float) $product->minimum_stock,
            ];
        });
    }

    /**
     * @param  array{from?: string, to?: string, type?: string, reference?: string, search?: string}  $filters
     */
    public function movementQuery(Product $product, array $filters = []): Builder
    {
        $query = StockMovement::query()
            ->where('product_id', $product->id)
            ->orderBy('movement_date')
            ->orderBy('created_at')
            ->orderBy('id');

        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));
        $type = trim((string) ($filters['type'] ?? ''));
        $reference = trim((string) ($filters['reference'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));

        if ($from !== '') {
            $query->where('movement_date', '>=', $from);
        }

        if ($to !== '') {
            $query->where('movement_date', '<=', $to);
        }

        if ($type !== '') {
            if (str_contains($type, ':')) {
                [$movementType, $direction] = explode(':', $type, 2);
                $query->where('movement_type', $movementType)->where('direction', $direction);
            } else {
                $query->where('movement_type', $type);
            }
        }

        if ($reference !== '') {
            $query->where('reference', 'like', '%'.$reference.'%');
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('notes', 'like', '%'.$search.'%')
                    ->orWhere('movement_type', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    /**
     * @param  array{from?: string, to?: string, type?: string, reference?: string, search?: string}  $filters
     * @return Collection<int, StockMovement>
     */
    public function ledgerRows(Product $product, array $filters = []): Collection
    {
        $balances = $this->runningBalances($product);

        return $this->movementQuery($product, $filters)->get()->each(function (StockMovement $movement) use ($balances): void {
            $movement->setAttribute('balance', $balances[$movement->id] ?? 0.0);
        });
    }

    /**
     * Running balance after each movement, keyed by movement id.
     *
     * @return array<string, float>
     */
    public function runningBalances(Product $product): array
    {
        $balance = $this->openingStock($product);
        $balances = [];

        $this->movementQuery($product)->get(['id', 'direction', 'quantity'])->each(function (StockMovement $movement) use (&$balance, &$balances): void {
            $balance += $movement->signedQuantity();
            $balances[$movement->id] = $balance;
        });

        return $balances;
    }

    /**
     * @return array{opening: float, in: float, out: float, balance: float}
     */
    public function stockTotals(Product $product): array
    {
        $totals = StockMovement::query()
            ->where('product_id', $product->id)
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'in' THEN quantity ELSE 0 END), 0) as total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN direction = 'out' THEN quantity ELSE 0 END), 0) as total_out")
            ->first();

        $in = (float) ($totals->total_in ?? 0);
        $out = (float) ($totals->total_out ?? 0);

        return [
            'opening' => $this->openingStock($product),
            'in' => $in,
            'out' => $out,
            'balance' => $this->openingStock($product) + $in - $out,
        ];
    }

    public function recordSale(SalesInvoice $invoice, User $user): void
    {
        $invoice->load('lines.product');

        foreach ($invoice->lines as $line) {
            if ($line->product_id === null) {
                continue;
            }

            $product = $line->product;
            $quantity = (float) $line->quantity;
            $available = $this->currentStock($product);

            if ($quantity > $available) {
                throw InsufficientStockException::forProduct($product->name, $quantity, $available);
            }

            $this->recordMovement(
                product: $product,
                movementDate: $invoice->invoice_date,
                movementType: StockMovementType::Sale,
                direction: StockDirection::Out,
                quantity: $quantity,
                sourceType: 'sales_invoice',
                sourceId: $invoice->id,
                reference: $invoice->invoice_number,
                notes: 'Sales invoice '.$invoice->invoice_number,
                user: $user,
            );
        }
    }

    public function reverseSale(SalesInvoice $invoice, User $user): void
    {
        $invoice->load('lines.product');

        foreach ($invoice->lines as $line) {
            if ($line->product_id === null) {
                continue;
            }

            $this->recordMovement(
                product: $line->product,
                movementDate: now(),
                movementType: StockMovementType::Reversal,
                direction: StockDirection::In,
                quantity: (float) $line->quantity,
                sourceType: 'sales_invoice',
                sourceId: $invoice->id,
                reference: $invoice->invoice_number,
                notes: 'Reversal of sales invoice '.$invoice->invoice_number,
                user: $user,
            );
        }
    }

    public function recordPurchase(PurchaseBill $bill, User $user): void
    {
        $bill->load('lines.product');

        foreach ($bill->lines as $line) {
            if ($line->product_id === null) {
                continue;
            }

            $this->recordMovement(
                product: $line->product,
                movementDate: $bill->bill_date,
                movementType: StockMovementType::Purchase,
                direction: StockDirection::In,
                quantity: (float) $line->quantity,
                sourceType: 'purchase_bill',
                sourceId: $bill->id,
                reference: $bill->bill_number,
                notes: 'Purchase bill '.$bill->bill_number,
                user: $user,
            );
        }
    }

    public function reversePurchase(PurchaseBill $bill, User $user): void
    {
        $bill->load('lines.product');

        foreach ($bill->lines as $line) {
            if ($line->product_id === null) {
                continue;
            }

            $product = $line->product;
            $quantity = (float) $line->quantity;
            $available = $this->currentStock($product);

            if ($quantity > $available) {
                throw InsufficientStockException::forProduct($product->name, $quantity, $available);
            }

            $this->recordMovement(
                product: $product,
                movementDate: now(),
                movementType: StockMovementType::Reversal,
                direction: StockDirection::Out,
                quantity: $quantity,
                sourceType: 'purchase_bill',
                sourceId: $bill->id,
                reference: $bill->bill_number,
                notes: 'Reversal of purchase bill '.$bill->bill_number,
                user: $user,
            );
        }
    }

    public function recordSaleReturn(CreditNote $creditNote, User $user): void
    {
        $creditNote->load('lines.product');

        foreach ($creditNote->lines as $line) {
            if ($line->product_id === null) {
                continue;
            }

            $this->recordMovement(
                product: $line->product,
                movementDate: $creditNote->credit_note_date,
                movementType: StockMovementType::SaleReturn,
                direction: StockDirection::In,
                quantity: (float) $line->quantity,
                sourceType: 'credit_note',
                sourceId: $creditNote->id,
                reference: $creditNote->credit_note_number,
                notes: 'Credit note '.$creditNote->credit_note_number,
                user: $user,
            );
        }
    }

    public function recordPurchaseReturn(DebitNote $debitNote, User $user): void
    {
        $debitNote->load('lines.product');

        foreach ($debitNote->lines as $line) {
            if ($line->product_id === null) {
                continue;
            }

            $product = $line->product;
            $quantity = (float) $line->quantity;
            $available = $this->currentStock($product);

            if ($quantity > $available) {
                throw InsufficientStockException::forProduct($product->name, $quantity, $available);
            }

            $this->recordMovement(
                product: $product,
                movementDate: $debitNote->debit_note_date,
                movementType: StockMovementType::PurchaseReturn,
                direction: StockDirection::Out,
                quantity: $quantity,
                sourceType: 'debit_note',
                sourceId: $debitNote->id,
                reference: $debitNote->debit_note_number,
                notes: 'Debit note '.$debitNote->debit_note_number,
                user: $user,
            );
        }
    }

    public function adjust(
        Product $product,
        User $user,
        float $quantity,
        StockDirection $direction,
        Carbon|string $movementDate,
        ?string $notes = null,
        ?string $reference = null,
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Adjustment quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($product, $user, $quantity, $direction, $movementDate, $notes, $reference): StockMovement {
            if ($direction === StockDirection::Out) {
                $available = $this->currentStock($product);

                if ($quantity > $available) {
                    throw InsufficientStockException::forProduct($product->name, $quantity, $available);
                }
            }

            return $this->recordMovement(
                product: $product,
                movementDate: $movementDate,
                movementType: StockMovementType::Adjustment,
                direction: $direction,
                quantity: $quantity,
                sourceType: 'product',
                sourceId: $product->id,
                reference: $reference,
                notes: $notes ?? 'Stock adjustment',
                user: $user,
            );
        });
    }

    private function recordMovement(
        Product $product,
        Carbon|string $movementDate,
        StockMovementType $movementType,
        StockDirection $direction,
        float $quantity,
        ?string $sourceType,
        ?string $sourceId,
        ?string $reference,
        ?string $notes,
        User $user,
    ): StockMovement {
        return DB::transaction(function () use (
            $product,
            $movementDate,
            $movementType,
            $direction,
            $quantity,
            $sourceType,
            $sourceId,
            $reference,
            $notes,
            $user,
        ): StockMovement {
            return StockMovement::query()->create([
                'business_id' => $product->business_id,
                'product_id' => $product->id,
                'movement_date' => Carbon::parse($movementDate)->toDateString(),
                'movement_type' => $movementType,
                'direction' => $direction,
                'quantity' => $quantity,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reference' => $reference,
                'notes' => $notes,
                'created_by' => $user->id,
            ]);
        });
    }
}
