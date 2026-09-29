<?php

namespace App\Livewire\Accounting;

use App\Application\Services\BankReconciliationService;
use App\Domain\Accounting\Enums\AccountType;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Account;
use App\Models\BankStatementLine;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class BankReconciliation extends Component
{
    use DispatchesToasts;

    public ?string $accountId = null;

    public ?string $selectedLineId = null;

    public ?string $selectedPaymentId = null;

    public function mount(): void
    {
        $this->accountId = Account::query()
            ->where('type', AccountType::Asset)
            ->where('code', '1200')
            ->whereNotNull('parent_id')
            ->value('id');
    }

    public function match(BankReconciliationService $bankReconciliationService): void
    {
        $this->authorize('view_ledger');

        $this->validate([
            'selectedLineId' => ['required', 'uuid'],
            'selectedPaymentId' => ['required', 'uuid'],
        ]);

        $line = BankStatementLine::query()->findOrFail($this->selectedLineId);
        $payment = Payment::query()->findOrFail($this->selectedPaymentId);

        $bankReconciliationService->matchToPayment($line, $payment);

        $this->selectedLineId = null;
        $this->selectedPaymentId = null;

        $this->toastSuccess(__('Statement line matched to payment.'));
    }

    public function render(BankReconciliationService $bankReconciliationService): View
    {
        $this->authorize('view_ledger');

        $accounts = Account::query()
            ->where('type', AccountType::Asset)
            ->whereNotNull('parent_id')
            ->where('code', 'like', '12%')
            ->orderBy('code')
            ->get();

        $selectedAccount = $accounts->firstWhere('id', $this->accountId);
        $statementLines = collect();
        $payments = collect();

        if ($selectedAccount !== null && business_context()->hasBusiness()) {
            $business = business_context()->business();

            $statementLines = $bankReconciliationService->unmatchedLines($business, $selectedAccount);
            $payments = $bankReconciliationService->unmatchedPayments($business, $selectedAccount);
        }

        return view('livewire.accounting.bank-reconciliation', [
            'accounts' => $accounts,
            'selectedAccount' => $selectedAccount,
            'statementLines' => $statementLines,
            'payments' => $payments,
        ]);
    }
}
