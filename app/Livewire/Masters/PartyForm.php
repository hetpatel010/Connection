<?php

namespace App\Livewire\Masters;

use App\Application\Services\PartyService;
use App\Domain\Parties\Enums\PartyType;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Party;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PartyForm extends Component
{
    use DispatchesToasts;

    public ?Party $party = null;

    public string $partyType = 'customer';

    public string $name = '';

    public string $legalName = '';

    public string $phone = '';

    public string $email = '';

    public string $gstin = '';

    public string $pan = '';

    public string $billingState = '';

    public string $billingCity = '';

    public string $billingAddressLine1 = '';

    public string $paymentTerms = '';

    public string $openingBalance = '0';

    public string $notes = '';

    public function mount(?Party $party = null): void
    {
        $this->billingState = business_context()->business()?->state ?? '';

        if ($party !== null && $party->exists) {
            $this->authorize('update', $party);
            $this->party = $party;
            $this->fillFromParty($party);
        } else {
            $this->authorizeCreate();
        }
    }

    public function save(PartyService $partyService): void
    {
        if ($this->party === null) {
            $this->authorizeCreate();
        } else {
            $this->authorize('update', $this->party);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'legalName' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'gstin' => ['nullable', 'string', 'max:15'],
            'pan' => ['nullable', 'string', 'max:10'],
            'billingState' => ['nullable', 'string', 'max:100'],
            'billingCity' => ['nullable', 'string', 'max:100'],
            'billingAddressLine1' => ['nullable', 'string', 'max:255'],
            'paymentTerms' => ['nullable', 'string', 'max:100'],
            'openingBalance' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'party_type' => $this->partyType,
            'name' => $validated['name'],
            'legal_name' => $validated['legalName'] ?: null,
            'phone' => $validated['phone'] ?: null,
            'email' => $validated['email'] ?: null,
            'gstin' => $validated['gstin'] ?: null,
            'pan' => $validated['pan'] ?: null,
            'billing_state' => $validated['billingState'] ?: null,
            'billing_city' => $validated['billingCity'] ?: null,
            'billing_address_line_1' => $validated['billingAddressLine1'] ?: null,
            'payment_terms' => $validated['paymentTerms'] ?: null,
            'opening_balance' => $validated['openingBalance'] ?: 0,
            'notes' => $validated['notes'] ?: null,
        ];

        if ($this->party !== null) {
            $partyService->update($this->party, $payload);
            $this->toastSuccess(__('Party updated successfully.'));
        } else {
            $partyService->create(business_context()->business(), $payload);
            $this->toastSuccess(__('Party created successfully.'));
        }

        $route = in_array($this->partyType, ['supplier', PartyType::Supplier->value], true)
            ? 'masters.suppliers.index'
            : 'masters.customers.index';

        $this->redirectRoute($route, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.masters.party-form');
    }

    private function authorizeCreate(): void
    {
        $permission = $this->partyType === PartyType::Supplier->value
            ? 'manage_suppliers'
            : 'manage_customers';

        abort_unless(business_context()->userCan($permission), 403);
    }

    private function fillFromParty(Party $party): void
    {
        $this->partyType = $party->party_type->value;
        $this->name = $party->name;
        $this->legalName = $party->legal_name ?? '';
        $this->phone = $party->phone ?? '';
        $this->email = $party->email ?? '';
        $this->gstin = $party->gstin ?? '';
        $this->pan = $party->pan ?? '';
        $this->billingState = $party->billing_state ?? '';
        $this->billingCity = $party->billing_city ?? '';
        $this->billingAddressLine1 = $party->billing_address_line_1 ?? '';
        $this->paymentTerms = $party->payment_terms ?? '';
        $this->openingBalance = (string) ($party->opening_balance / 100);
        $this->notes = $party->notes ?? '';
    }
}
