<?php

namespace App\Models;

use App\Domain\Accounting\Enums\AccountStatus;
use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\BalanceSide;
use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'parent_id',
    'name',
    'code',
    'type',
    'group',
    'status',
    'is_system',
    'description',
    'opening_balance',
    'opening_balance_side',
    'version',
])]
class Account extends Model
{
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'status' => AccountStatus::class,
            'opening_balance_side' => BalanceSide::class,
            'is_system' => 'boolean',
            'opening_balance' => 'integer',
            'version' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function bankStatementLines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expensesAsExpense(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_account_id');
    }

    public function expensesAsPayment(): HasMany
    {
        return $this->hasMany(Expense::class, 'payment_account_id');
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class, 'payment_account_id');
    }

    public function purchaseBills(): HasMany
    {
        return $this->hasMany(PurchaseBill::class, 'payment_account_id');
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class, 'payment_account_id');
    }

    public function debitNotes(): HasMany
    {
        return $this->hasMany(DebitNote::class, 'payment_account_id');
    }

    public function scopePostable(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id')
            ->where('status', AccountStatus::Active);
    }

    /**
     * Shared chart of accounts filtering so the listing and every export stay in sync.
     *
     * @param  array{search?: string, type?: string, group?: string, status?: string, view?: string}  $filters
     */
    public function scopeFiltered(Builder $query, array $filters): Builder
    {
        $search = trim($filters['search'] ?? '');
        $type = trim($filters['type'] ?? '');
        $group = trim($filters['group'] ?? '');
        $status = trim($filters['status'] ?? '');

        return $query
            ->when($search !== '', function (Builder $builder) use ($search): void {
                $builder->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('group', 'like', '%'.$search.'%')
                        ->orWhere('type', 'like', '%'.$search.'%');
                });
            })
            ->when($type !== '', fn (Builder $builder) => $builder->where('type', $type))
            ->when($group !== '', fn (Builder $builder) => $builder->where('group', $group))
            ->when($status !== '', fn (Builder $builder) => $builder->where('status', $status))
            ->when(($filters['view'] ?? 'all') === 'postable', fn (Builder $builder) => $builder->whereNotNull('parent_id'))
            ->when(($filters['view'] ?? 'all') === 'groups', fn (Builder $builder) => $builder->whereNull('parent_id'))
            ->orderBy('code');
    }

    public function isGroupHeader(): bool
    {
        return $this->parent_id === null;
    }

    public function isPostable(): bool
    {
        return $this->parent_id !== null && $this->status === AccountStatus::Active;
    }
}
