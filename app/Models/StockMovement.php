<?php

namespace App\Models;

use App\Domain\Inventory\Enums\StockDirection;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'product_id',
    'movement_date',
    'movement_type',
    'direction',
    'quantity',
    'source_type',
    'source_id',
    'reference',
    'notes',
    'created_by',
])]
class StockMovement extends Model
{
    use BelongsToBusiness, HasUuid;

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'movement_type' => StockMovementType::class,
            'direction' => StockDirection::class,
            'quantity' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isIncoming(): bool
    {
        return $this->direction === StockDirection::In;
    }

    public function signedQuantity(): float
    {
        return $this->isIncoming() ? (float) $this->quantity : -1 * (float) $this->quantity;
    }

    public function ledgerLabel(): string
    {
        if ($this->movement_type === StockMovementType::Adjustment) {
            return $this->isIncoming() ? 'Stock Adjustment In' : 'Stock Adjustment Out';
        }

        return $this->movement_type->label();
    }
}
