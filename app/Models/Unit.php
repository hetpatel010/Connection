<?php

namespace App\Models;

use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use Database\Factories\UnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'name',
    'symbol',
    'decimal_places',
    'is_default',
    'is_system',
])]
class Unit extends Model
{
    /** @use HasFactory<UnitFactory> */
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_default' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
