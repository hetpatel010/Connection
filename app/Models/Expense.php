<?php

namespace App\Models;

use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use App\Domain\Transactions\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'financial_year_id',
    'expense_number',
    'expense_date',
    'expense_account_id',
    'payment_account_id',
    'party_id',
    'amount',
    'tax_amount',
    'description',
    'reference',
    'status',
    'journal_id',
    'confirmed_at',
    'confirmed_by',
    'created_by',
    'version',
])]
class Expense extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'status' => DocumentStatus::class,
            'amount' => 'integer',
            'tax_amount' => 'integer',
            'confirmed_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}
