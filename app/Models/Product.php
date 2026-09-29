<?php

namespace App\Models;

use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use App\Domain\Shared\Enums\RecordStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'category_id',
    'unit_id',
    'name',
    'sku',
    'barcode',
    'hsn_sac',
    'brand',
    'purchase_price',
    'selling_price',
    'tax_rate',
    'tax_inclusive',
    'minimum_stock',
    'opening_stock',
    'description',
    'image_path',
    'status',
    'version',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => RecordStatus::class,
            'purchase_price' => 'integer',
            'selling_price' => 'integer',
            'tax_rate' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'minimum_stock' => 'decimal:4',
            'opening_stock' => 'decimal:4',
            'version' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
