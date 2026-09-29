<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('manage_chart_of_accounts');
    }

    public function view(User $user, Account $account): bool
    {
        return $this->belongsToBusiness($user, $account)
            && business_context()->userCan('manage_chart_of_accounts');
    }

    public function create(User $user): bool
    {
        return business_context()->userCan('manage_chart_of_accounts');
    }

    public function update(User $user, Account $account): bool
    {
        return $this->view($user, $account);
    }

    public function delete(User $user, Account $account): bool
    {
        return $this->view($user, $account);
    }

    private function belongsToBusiness(User $user, Account $account): bool
    {
        return $user->belongsToBusiness($account->business);
    }
}
