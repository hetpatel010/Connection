<?php

namespace App\Livewire\Accounting;

use App\Application\Services\AccountingService;
use App\Domain\Accounting\DTOs\JournalLineData;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Account;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateJournal extends Component
{
    use DispatchesToasts;

    public string $transactionDate = '';

    public string $description = '';

    /** @var array<int, array{account_id: string, debit: string, credit: string, line_description: string}> */
    public array $lines = [];

    public function mount(): void
    {
        $this->transactionDate = now()->toDateString();
        $this->lines = [
            ['account_id' => '', 'debit' => '', 'credit' => '', 'line_description' => ''],
            ['account_id' => '', 'debit' => '', 'credit' => '', 'line_description' => ''],
        ];
    }

    public function addLine(): void
    {
        $this->lines[] = ['account_id' => '', 'debit' => '', 'credit' => '', 'line_description' => ''];
    }

    public function removeLine(int $index): void
    {
        if (count($this->lines) <= 2) {
            return;
        }

        unset($this->lines[$index]);
        $this->lines = array_values($this->lines);
    }

    public function save(AccountingService $accountingService): void
    {
        $this->authorize('create_journal');

        $validated = $this->validate([
            'transactionDate' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'uuid'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.line_description' => ['nullable', 'string', 'max:255'],
        ]);

        $journalLines = collect($validated['lines'])->map(function (array $line): JournalLineData {
            return new JournalLineData(
                accountId: $line['account_id'],
                debitAmount: Money::rupeesToPaise($line['debit'] ?: 0),
                creditAmount: Money::rupeesToPaise($line['credit'] ?: 0),
                description: $line['line_description'] ?: null,
            );
        })->all();

        $journal = $accountingService->postJournal(
            business: business_context()->business(),
            financialYear: business_context()->financialYear(),
            user: auth()->user(),
            transactionDate: $validated['transactionDate'],
            description: $validated['description'],
            lines: $journalLines,
            sourceType: 'manual_journal',
        );

        $this->toastSuccess('Journal '.$journal->journal_number.' posted successfully.');

        $this->redirectRoute('accounting.journals.create', navigate: true);
    }

    public function render(): View
    {
        $accounts = Account::query()
            ->postable()
            ->orderBy('code')
            ->get();

        return view('livewire.accounting.create-journal', [
            'accounts' => $accounts,
        ]);
    }
}
