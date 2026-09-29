<?php

namespace App\Application\Services;

use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Enums\BalanceSide;
use App\Models\Account;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OpeningBalanceCarryForwardService
{
    public function __construct(
        private LedgerService $ledgerService,
        private ProfitAndLossService $profitAndLossService,
        private ChartOfAccountsService $chartOfAccountsService,
        private AccountLookupService $accountLookup,
    ) {}

    public function carryForward(
        Business $business,
        FinancialYear $closedYear,
        FinancialYear $newYear,
        User $user,
    ): void {
        DB::transaction(function () use ($business, $closedYear, $newYear, $user): void {
            Account::query()
                ->where('business_id', $business->id)
                ->whereNotNull('parent_id')
                ->update([
                    'opening_balance' => 0,
                    'opening_balance_side' => null,
                ]);

            $asOf = $closedYear->ends_on;

            $accounts = Account::query()
                ->where('business_id', $business->id)
                ->whereNotNull('parent_id')
                ->whereIn('type', [AccountType::Asset, AccountType::Liability, AccountType::Equity])
                ->where('status', 'active')
                ->get();

            foreach ($accounts as $account) {
                $balance = $this->ledgerService->signedBalance($account, $closedYear, $asOf);

                if ($balance['amount'] === 0) {
                    continue;
                }

                $account->update([
                    'opening_balance' => $balance['amount'],
                    'opening_balance_side' => $balance['side'],
                ]);
            }

            $profitAndLoss = $this->profitAndLossService->generate(
                $business,
                $closedYear,
                $closedYear->starts_on,
                $closedYear->ends_on,
            );

            $netProfit = $profitAndLoss['net_profit'];

            if ($netProfit !== 0) {
                $capitalAccount = $this->accountLookup->byCode($business, '3100');
                $capitalAccount->refresh();

                $this->applyNetProfitToCapital($capitalAccount, $netProfit);
            }

            $this->chartOfAccountsService->postOpeningBalances(
                $business,
                $newYear,
                $user,
                app(AccountingService::class),
            );
        });
    }

    private function applyNetProfitToCapital(Account $capitalAccount, int $netProfit): void
    {
        $signedAmount = $this->signedEquityAmount(
            $capitalAccount->opening_balance,
            $capitalAccount->opening_balance_side,
        );

        $signedAmount += $netProfit;

        if ($signedAmount === 0) {
            $capitalAccount->update([
                'opening_balance' => 0,
                'opening_balance_side' => null,
            ]);

            return;
        }

        $capitalAccount->update([
            'opening_balance' => abs($signedAmount),
            'opening_balance_side' => $signedAmount > 0 ? BalanceSide::Credit : BalanceSide::Debit,
        ]);
    }

    private function signedEquityAmount(int $amount, ?BalanceSide $side): int
    {
        if ($amount === 0 || $side === null) {
            return 0;
        }

        return $side === BalanceSide::Credit ? $amount : -$amount;
    }
}
