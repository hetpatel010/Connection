<?php

namespace App\Policies;

use App\Models\DebitNote;
use App\Models\User;

class DebitNotePolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('view_purchase');
    }

    public function view(User $user, DebitNote $debitNote): bool
    {
        return $user->belongsToBusiness($debitNote->business)
            && business_context()->userCan('view_purchase');
    }

    public function create(User $user): bool
    {
        return business_context()->userCan('create_purchase');
    }

    public function reverse(User $user, DebitNote $debitNote): bool
    {
        return $user->belongsToBusiness($debitNote->business)
            && business_context()->userCan('reverse_purchase');
    }
}
