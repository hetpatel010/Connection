<?php

namespace App\Models;

use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'business_id',
    'account_id',
    'transaction_date',
    'description',
    'reference',
    'amount',
    'matched_payment_id',
    'matched_at',
])]
class BankStatementLine extends Model
{
    use BelongsToBusiness, HasUuid;

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'integer',
            'matched_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function matchedPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'matched_payment_id');
    }
}
