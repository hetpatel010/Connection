<?php

namespace App\Policies;

use App\Models\CreditNote;
use App\Models\User;

class CreditNotePolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('view_invoice');
    }

    public function view(User $user, CreditNote $creditNote): bool
    {
        return $user->belongsToBusiness($creditNote->business)
            && business_context()->userCan('view_invoice');
    }

    public function create(User $user): bool
    {
        return business_context()->userCan('create_invoice');
    }

    public function reverse(User $user, CreditNote $creditNote): bool
    {
        return $user->belongsToBusiness($creditNote->business)
            && business_context()->userCan('reverse_invoice');
    }
}
