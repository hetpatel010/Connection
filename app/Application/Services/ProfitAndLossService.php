<?php

namespace App\Application\Services;

use App\Domain\Accounting\Enums\AccountType;
use App\Models\Account;
use App\Models\Business;
use App\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProfitAndLossService
{
    public function __construct(private LedgerService $ledgerService) {}

    /**
     * @return array{
     *     income: Collection<int, array{code: string, name: string, amount: int}>,
     *     expenses: Collection<int, array{code: string, name: string, amount: int}>,
     *     total_income: int,
     *     total_expenses: int,
     *     net_profit: int,
     *     from: string,
     *     to: string
     * }
     */
    public function generate(
        Business $business,
        FinancialYear $financialYear,
        Carbon $from,
        Carbon $to,
    ): array {
        $accounts = Account::query()
            ->where('business_id', $business->id)
            ->whereNotNull('parent_id')
            ->whereIn('type', [AccountType::Income, AccountType::Expense])
            ->where('status', 'active')
            ->orderBy('code')
            ->get();

        $income = collect();
        $expenses = collect();

        foreach ($accounts as $account) {
            $totals = $this->ledgerService->periodTotals($account, $financialYear, $from, $to);

            if ($account->type === AccountType::Income) {
                $amount = $totals['credits'] - $totals['debits'];

                if ($amount !== 0) {
                    $income->push([
                        'code' => $account->code,
                        'name' => $account->name,
                        'amount' => $amount,
                    ]);
                }
            } else {
                $amount = $totals['debits'] - $totals['credits'];

                if ($amount !== 0) {
                    $expenses->push([
                        'code' => $account->code,
                        'name' => $account->name,
                        'amount' => $amount,
                    ]);
                }
            }
        }

        $totalIncome = (int) $income->sum('amount');
        $totalExpenses = (int) $expenses->sum('amount');

        return [
            'income' => $income->values(),
            'expenses' => $expenses->values(),
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalIncome - $totalExpenses,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ];
    }
}
