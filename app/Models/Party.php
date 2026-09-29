<?php

namespace App\Models;

use App\Domain\Accounting\Enums\BalanceSide;
use App\Domain\Parties\Enums\PartyType;
use App\Domain\Shared\Concerns\BelongsToBusiness;
use App\Domain\Shared\Concerns\HasUuid;
use App\Domain\Shared\Enums\RecordStatus;
use Database\Factories\PartyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'business_id',
    'party_type',
    'name',
    'legal_name',
    'phone',
    'email',
    'gstin',
    'pan',
    'billing_address_line_1',
    'billing_address_line_2',
    'billing_city',
    'billing_state',
    'billing_country',
    'billing_postal_code',
    'shipping_address_line_1',
    'shipping_address_line_2',
    'shipping_city',
    'shipping_state',
    'shipping_country',
    'shipping_postal_code',
    'credit_limit',
    'payment_terms',
    'opening_balance',
    'opening_balance_side',
    'notes',
    'tags',
    'status',
    'version',
])]
class Party extends Model
{
    /** @use HasFactory<PartyFactory> */
    use BelongsToBusiness, HasFactory, HasUuid, SoftDeletes;

    protected function casts(): array
    {
        return [
            'party_type' => PartyType::class,
            'status' => RecordStatus::class,
            'credit_limit' => 'integer',
            'opening_balance' => 'integer',
            'opening_balance_side' => BalanceSide::class,
            'tags' => 'array',
            'version' => 'integer',
        ];
    }

    public function scopeCustomers($query)
    {
        return $query->whereIn('party_type', [PartyType::Customer, PartyType::Both]);
    }

    public function scopeSuppliers($query)
    {
        return $query->whereIn('party_type', [PartyType::Supplier, PartyType::Both]);
    }
}
