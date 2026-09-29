<?php

namespace App\Policies;

use App\Domain\FinancialYear\Enums\FinancialYearStatus;
use App\Models\FinancialYear;
use App\Models\User;

class FinancialYearPolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('close_financial_year');
    }

    public function close(User $user, FinancialYear $financialYear): bool
    {
        $business = business_context()->business();

        if ($business === null || $financialYear->business_id !== $business->id) {
            return false;
        }

        return $user->hasPermissionInBusiness('close_financial_year', $business)
            && $financialYear->status === FinancialYearStatus::Open;
    }

    public function openNext(User $user): bool
    {
        return business_context()->userCan('close_financial_year');
    }
}
