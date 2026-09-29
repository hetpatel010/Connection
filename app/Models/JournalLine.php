<?php

namespace App\Models;

use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'financial_year_id',
    'journal_id',
    'account_id',
    'debit_amount',
    'credit_amount',
    'description',
    'line_number',
])]
class JournalLine extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid;

    protected function casts(): array
    {
        return [
            'debit_amount' => 'integer',
            'credit_amount' => 'integer',
            'line_number' => 'integer',
        ];
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}
