<?php

namespace App\Models;

use App\Domain\Accounting\Enums\JournalStatus;
use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'financial_year_id',
    'journal_number',
    'transaction_date',
    'description',
    'status',
    'source_type',
    'source_id',
    'reversal_of_id',
    'reversed_by_id',
    'posted_at',
    'posted_by',
    'created_by',
    'version',
])]
class Journal extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'status' => JournalStatus::class,
            'posted_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('line_number');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_by_id');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalDebits(): int
    {
        return (int) $this->lines->sum('debit_amount');
    }

    public function totalCredits(): int
    {
        return (int) $this->lines->sum('credit_amount');
    }

    public function isBalanced(): bool
    {
        return $this->totalDebits() === $this->totalCredits() && $this->totalDebits() > 0;
    }
}
