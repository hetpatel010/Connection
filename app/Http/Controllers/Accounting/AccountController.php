<?php

namespace App\Http\Controllers\Accounting;

use App\Application\Services\ChartOfAccountsService;
use App\Domain\Accounting\Exceptions\AccountHasTransactionsException;
use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;

class AccountController extends Controller
{
    public function destroy(Account $account, ChartOfAccountsService $chartOfAccountsService): RedirectResponse
    {
        $this->authorize('delete', $account);

        try {
            $chartOfAccountsService->deleteAccount($account);
        } catch (AccountHasTransactionsException $exception) {
            return redirect()->route('accounting.accounts.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('accounting.accounts.index')
            ->with('status', __('Account :code deleted successfully.', ['code' => $account->code]));
    }
}
