<?php

namespace App\Livewire\Payments;

use App\Application\Services\PaymentAllocationService;
use App\Application\Services\PaymentService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Account;
use App\Models\Party;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreatePayment extends Component
{
    use DispatchesToasts;

    public string $paymentType = 'payment_in';

    public string $paymentDate = '';

    public string $partyId = '';

    public string $paymentMode = 'cash';

    public string $accountId = '';

    public string $amount = '';

    public string $reference = '';

    public string $notes = '';

    /** @var array<string, string> */
    public array $allocationAmounts = [];

    public function mount(string $paymentType = 'payment_in'): void
    {
        $this->authorize('create', Payment::class);
        $this->paymentType = $paymentType;
        $this->paymentDate = now()->toDateString();
    }

    public function updatedPartyId(): void
    {
        $this->allocationAmounts = [];
    }

    public function updatedPaymentType(): void
    {
        $this->partyId = '';
        $this->allocationAmounts = [];
    }

    public function save(PaymentService $paymentService): void
    {
        $this->authorize('create', Payment::class);

        $validated = $this->validate([
            'paymentType' => ['required', 'in:payment_in,payment_out'],
            'paymentDate' => ['required', 'date'],
            'partyId' => ['nullable', 'uuid'],
            'paymentMode' => ['required', 'in:cash,bank'],
            'accountId' => ['nullable', 'uuid'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'allocationAmounts' => ['array'],
            'allocationAmounts.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $allocatableType = $this->paymentType === 'payment_out' ? 'purchase_bill' : 'sales_invoice';
        $allocations = [];

        foreach ($this->allocationAmounts as $documentId => $allocationAmount) {
            if ($allocationAmount === '' || (float) $allocationAmount <= 0) {
                continue;
            }

            $allocations[] = [
                'allocatable_type' => $allocatableType,
                'allocatable_id' => $documentId,
                'allocated_amount' => Money::rupeesToPaise($allocationAmount),
            ];
        }

        $payment = $paymentService->createAndConfirm(
            business: business_context()->business(),
            financialYear: business_context()->financialYear(),
            user: auth()->user(),
            attributes: [
                'payment_type' => $validated['paymentType'],
                'payment_date' => $validated['paymentDate'],
                'party_id' => $validated['partyId'] ?: null,
                'payment_mode' => $validated['paymentMode'],
                'account_id' => $validated['accountId'] ?: null,
                'amount' => Money::rupeesToPaise($validated['amount']),
                'reference' => $validated['reference'] ?: null,
                'notes' => $validated['notes'] ?: null,
                'allocations' => $allocations,
            ],
        );

        $this->toastSuccess(__('Payment :number recorded.', ['number' => $payment->payment_number]));

        $this->redirectRoute('payments.index', navigate: true);
    }

    public function render(PaymentAllocationService $allocationService): View
    {
        $parties = $this->paymentType === 'payment_out'
            ? Party::query()->suppliers()->orderBy('name')->get()
            : Party::query()->customers()->orderBy('name')->get();

        $openDocuments = collect();

        if ($this->partyId !== '') {
            $business = business_context()->business();
            $openDocuments = $this->paymentType === 'payment_out'
                ? $allocationService->openPurchaseBills($business, $this->partyId)
                : $allocationService->openSalesInvoices($business, $this->partyId);
        }

        return view('livewire.payments.create-payment', [
            'parties' => $parties,
            'paymentAccounts' => Account::query()->whereIn('code', ['1100', '1200'])->orderBy('code')->get(),
            'openDocuments' => $openDocuments,
            'allocationService' => $allocationService,
        ]);
    }
}
