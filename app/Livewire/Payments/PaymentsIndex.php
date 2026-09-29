<?php

namespace App\Livewire\Payments;

use App\Application\Services\PaymentService;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentsIndex extends Component
{
    use DispatchesToasts;
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function reversePayment(string $paymentId, PaymentService $paymentService): void
    {
        $payment = Payment::query()->findOrFail($paymentId);
        $this->authorize('reverse', $payment);

        $paymentService->reverse($payment, auth()->user());

        $this->toastSuccess(__('Payment :number reversed.', ['number' => $payment->payment_number]));
    }

    public function render(): View
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['party', 'account', 'allocations'])
            ->when($this->type !== '', fn ($query) => $query->where('payment_type', $this->type))
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($builder): void {
                    $builder->where('payment_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('party', fn ($party) => $party->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->orderByDesc('payment_date')
            ->paginate(15);

        return view('livewire.payments.payments-index', [
            'payments' => $payments,
        ]);
    }
}
