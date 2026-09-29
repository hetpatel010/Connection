<?php

namespace App\Application\Services;

use App\Domain\Parties\Enums\PartyType;
use App\Domain\Shared\Enums\RecordStatus;
use App\Domain\Shared\Exceptions\PartyHasTransactionsException;
use App\Models\Business;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Expense;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PurchaseBill;
use App\Models\SalesInvoice;
use App\Support\Money;

class PartyService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Business $business, array $attributes): Party
    {
        return Party::query()->create($this->mapAttributes($business, $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Party $party, array $attributes): Party
    {
        $party->update($this->mapAttributes($party->business, $attributes, $party));

        return $party->fresh();
    }

    public function deactivate(Party $party): Party
    {
        $party->update(['status' => RecordStatus::Inactive]);

        return $party->fresh();
    }

    public function delete(Party $party): void
    {
        $this->assertDeletable($party);

        $party->delete();
    }

    public function assertDeletable(Party $party): void
    {
        if ($this->hasLinkedTransactions($party)) {
            throw PartyHasTransactionsException::forParty($party->name);
        }
    }

    private function hasLinkedTransactions(Party $party): bool
    {
        return SalesInvoice::query()->where('party_id', $party->id)->exists()
            || PurchaseBill::query()->where('party_id', $party->id)->exists()
            || Payment::query()->where('party_id', $party->id)->exists()
            || Expense::query()->where('party_id', $party->id)->exists()
            || CreditNote::query()->where('party_id', $party->id)->exists()
            || DebitNote::query()->where('party_id', $party->id)->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function mapAttributes(Business $business, array $attributes, ?Party $existing = null): array
    {
        $partyType = isset($attributes['party_type'])
            ? ($attributes['party_type'] instanceof PartyType
                ? $attributes['party_type']
                : PartyType::from($attributes['party_type']))
            : ($existing?->party_type ?? PartyType::Customer);

        return [
            'business_id' => $business->id,
            'party_type' => $partyType,
            'name' => $attributes['name'],
            'legal_name' => $attributes['legal_name'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'email' => $attributes['email'] ?? null,
            'gstin' => $attributes['gstin'] ?? null,
            'pan' => $attributes['pan'] ?? null,
            'billing_address_line_1' => $attributes['billing_address_line_1'] ?? null,
            'billing_address_line_2' => $attributes['billing_address_line_2'] ?? null,
            'billing_city' => $attributes['billing_city'] ?? null,
            'billing_state' => $attributes['billing_state'] ?? $business->state,
            'billing_country' => $attributes['billing_country'] ?? 'IN',
            'billing_postal_code' => $attributes['billing_postal_code'] ?? null,
            'shipping_address_line_1' => $attributes['shipping_address_line_1'] ?? null,
            'shipping_address_line_2' => $attributes['shipping_address_line_2'] ?? null,
            'shipping_city' => $attributes['shipping_city'] ?? null,
            'shipping_state' => $attributes['shipping_state'] ?? null,
            'shipping_country' => $attributes['shipping_country'] ?? 'IN',
            'shipping_postal_code' => $attributes['shipping_postal_code'] ?? null,
            'credit_limit' => isset($attributes['credit_limit'])
                ? Money::rupeesToPaise($attributes['credit_limit'])
                : ($existing?->credit_limit ?? 0),
            'payment_terms' => $attributes['payment_terms'] ?? null,
            'opening_balance' => isset($attributes['opening_balance'])
                ? Money::rupeesToPaise($attributes['opening_balance'])
                : ($existing?->opening_balance ?? 0),
            'opening_balance_side' => $attributes['opening_balance_side'] ?? $existing?->opening_balance_side,
            'notes' => $attributes['notes'] ?? null,
            'status' => isset($attributes['status'])
                ? ($attributes['status'] instanceof RecordStatus
                    ? $attributes['status']
                    : RecordStatus::from($attributes['status']))
                : ($existing?->status ?? RecordStatus::Active),
        ];
    }
}
