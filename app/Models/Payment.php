<?php

namespace App\Models;

use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\PaymentMode;
use App\Domain\Transactions\Enums\PaymentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'financial_year_id',
    'payment_number',
    'payment_type',
    'party_id',
    'payment_date',
    'payment_mode',
    'account_id',
    'amount',
    'reference',
    'notes',
    'status',
    'journal_id',
    'confirmed_at',
    'confirmed_by',
    'created_by',
    'version',
])]
class Payment extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'payment_type' => PaymentType::class,
            'payment_mode' => PaymentMode::class,
            'status' => DocumentStatus::class,
            'amount' => 'integer',
            'confirmed_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}
