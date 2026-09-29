<?php

namespace App\Models;

use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'financial_year_id',
    'debit_note_number',
    'source_purchase_bill_id',
    'party_id',
    'debit_note_date',
    'settlement_mode',
    'payment_account_id',
    'status',
    'subtotal_amount',
    'tax_amount',
    'total_amount',
    'notes',
    'reference',
    'journal_id',
    'confirmed_at',
    'confirmed_by',
    'created_by',
    'version',
])]
class DebitNote extends Model
{
    use BelongsToBusiness, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'debit_note_date' => 'date',
            'settlement_mode' => SettlementMode::class,
            'status' => DocumentStatus::class,
            'subtotal_amount' => 'integer',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'confirmed_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function sourcePurchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class, 'source_purchase_bill_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DebitNoteLine::class)->orderBy('line_number');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class);
    }
}
