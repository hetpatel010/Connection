<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('view_expense');
    }

    public function view(User $user, Expense $expense): bool
    {
        return $user->belongsToBusiness($expense->business)
            && business_context()->userCan('view_expense');
    }

    public function create(User $user): bool
    {
        return business_context()->userCan('create_expense');
    }

    public function reverse(User $user, Expense $expense): bool
    {
        return $user->belongsToBusiness($expense->business)
            && business_context()->userCan('reverse_expense');
    }
}
