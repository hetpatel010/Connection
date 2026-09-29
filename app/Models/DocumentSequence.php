<?php

namespace App\Models;

use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'document_type',
    'prefix',
    'financial_year_id',
    'next_number',
])]
class DocumentSequence extends Model
{
    use BelongsToBusiness, HasUuid;

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
        ];
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}
