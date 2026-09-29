<?php

namespace App\Application\Services;

use App\Domain\Accounting\Enums\BalanceSide;
use App\Models\Account;
use App\Models\Business;
use App\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TrialBalanceService
{
    public function __construct(private LedgerService $ledgerService) {}

    /**
     * @return array{
     *     rows: Collection<int, array{
     *         account_id: string,
     *         code: string,
     *         name: string,
     *         type: string,
     *         debit_balance: int,
     *         credit_balance: int
     *     }>,
     *     total_debits: int,
     *     total_credits: int
     * }
     */
    public function generate(Business $business, FinancialYear $financialYear, ?Carbon $asOf = null): array
    {
        $accounts = Account::query()
            ->where('business_id', $business->id)
            ->whereNotNull('parent_id')
            ->where('status', 'active')
            ->orderBy('code')
            ->get();

        $rows = $accounts->map(function (Account $account) use ($financialYear, $asOf) {
            $balance = $this->ledgerService->signedBalance($account, $financialYear, $asOf);

            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type->value,
                'debit_balance' => $balance['side'] === BalanceSide::Debit ? $balance['amount'] : 0,
                'credit_balance' => $balance['side'] === BalanceSide::Credit ? $balance['amount'] : 0,
            ];
        })->filter(fn (array $row) => $row['debit_balance'] > 0 || $row['credit_balance'] > 0)->values();

        return [
            'rows' => $rows,
            'total_debits' => (int) $rows->sum('debit_balance'),
            'total_credits' => (int) $rows->sum('credit_balance'),
        ];
    }
}
