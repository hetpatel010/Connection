<?php

namespace App\Application\Services;

use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\BalanceSide;
use App\Models\Account;
use App\Models\Business;
use App\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BalanceSheetService
{
    public function __construct(
        private LedgerService $ledgerService,
        private ProfitAndLossService $profitAndLossService,
    ) {}

    /**
     * @return array{
     *     assets: Collection<int, array{code: string, name: string, amount: int}>,
     *     liabilities: Collection<int, array{code: string, name: string, amount: int}>,
     *     equity: Collection<int, array{code: string, name: string, amount: int}>,
     *     current_period_profit: int,
     *     total_assets: int,
     *     total_liabilities_and_equity: int,
     *     as_of: string
     * }
     */
    public function generate(
        Business $business,
        FinancialYear $financialYear,
        Carbon $asOf,
    ): array {
        $accounts = Account::query()
            ->where('business_id', $business->id)
            ->whereNotNull('parent_id')
            ->whereIn('type', [AccountType::Asset, AccountType::Liability, AccountType::Equity])
            ->where('status', 'active')
            ->orderBy('code')
            ->get();

        $assets = collect();
        $liabilities = collect();
        $equity = collect();

        foreach ($accounts as $account) {
            $balance = $this->ledgerService->signedBalance($account, $financialYear, $asOf);

            if ($balance['amount'] === 0) {
                continue;
            }

            $row = [
                'code' => $account->code,
                'name' => $account->name,
                'amount' => $balance['amount'],
            ];

            match ($account->type) {
                AccountType::Asset => $assets->push(
                    $balance['side'] === BalanceSide::Debit
                        ? $row
                        : ['code' => $row['code'], 'name' => $row['name'], 'amount' => -$row['amount']],
                ),
                AccountType::Liability => $liabilities->push(
                    $balance['side'] === BalanceSide::Credit
                        ? $row
                        : ['code' => $row['code'], 'name' => $row['name'], 'amount' => -$row['amount']],
                ),
                AccountType::Equity => $equity->push(
                    $balance['side'] === BalanceSide::Credit
                        ? $row
                        : ['code' => $row['code'], 'name' => $row['name'], 'amount' => -$row['amount']],
                ),
                default => null,
            };
        }

        $profitAndLoss = $this->profitAndLossService->generate(
            $business,
            $financialYear,
            $financialYear->starts_on,
            $asOf,
        );

        $currentPeriodProfit = $profitAndLoss['net_profit'];

        if ($currentPeriodProfit !== 0) {
            $equity->push([
                'code' => '—',
                'name' => 'Current Period Profit',
                'amount' => $currentPeriodProfit,
            ]);
        }

        $totalAssets = (int) $assets->sum('amount');
        $totalLiabilitiesAndEquity = (int) $liabilities->sum('amount') + (int) $equity->sum('amount');

        return [
            'assets' => $assets->values(),
            'liabilities' => $liabilities->values(),
            'equity' => $equity->values(),
            'current_period_profit' => $currentPeriodProfit,
            'total_assets' => $totalAssets,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'as_of' => $asOf->toDateString(),
        ];
    }
}
