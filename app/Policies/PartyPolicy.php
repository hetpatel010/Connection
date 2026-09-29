<?php

namespace App\Policies;

use App\Models\Party;
use App\Models\User;

class PartyPolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('view_customers')
            || business_context()->userCan('view_suppliers');
    }

    public function view(User $user, Party $party): bool
    {
        if (! $user->belongsToBusiness($party->business)) {
            return false;
        }

        if ($party->party_type->isCustomer()) {
            return business_context()->userCan('view_customers');
        }

        return business_context()->userCan('view_suppliers');
    }

    public function create(User $user): bool
    {
        return business_context()->userCan('manage_customers')
            || business_context()->userCan('manage_suppliers');
    }

    public function update(User $user, Party $party): bool
    {
        if (! $user->belongsToBusiness($party->business)) {
            return false;
        }

        if ($party->party_type->isCustomer()) {
            return business_context()->userCan('manage_customers');
        }

        return business_context()->userCan('manage_suppliers');
    }

    public function delete(User $user, Party $party): bool
    {
        return $this->update($user, $party);
    }
}
