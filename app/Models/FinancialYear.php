<?php

namespace App\Models;

use App\Domain\FinancialYear\Enums\FinancialYearStatus;
use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use Database\Factories\FinancialYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'label',
    'starts_on',
    'ends_on',
    'status',
    'is_current',
    'closed_at',
    'closed_by',
])]
class FinancialYear extends Model
{
    /** @use HasFactory<FinancialYearFactory> */
    use BelongsToBusiness, HasFactory, HasUuid;

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => FinancialYearStatus::class,
            'is_current' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }
}
