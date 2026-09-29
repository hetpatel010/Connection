<?php

namespace App\Policies;

use App\Models\PurchaseBill;
use App\Models\User;

class PurchaseBillPolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('view_purchase');
    }

    public function view(User $user, PurchaseBill $bill): bool
    {
        return $user->belongsToBusiness($bill->business)
            && business_context()->userCan('view_purchase');
    }

    public function create(User $user): bool
    {
        return business_context()->userCan('create_purchase');
    }

    public function update(User $user, PurchaseBill $bill): bool
    {
        return $user->belongsToBusiness($bill->business)
            && business_context()->userCan('edit_purchase')
            && $bill->status->isEditable();
    }

    public function delete(User $user, PurchaseBill $bill): bool
    {
        return $user->belongsToBusiness($bill->business)
            && business_context()->userCan('delete_purchase')
            && $bill->status->isEditable();
    }

    public function confirm(User $user, PurchaseBill $bill): bool
    {
        return $user->belongsToBusiness($bill->business)
            && business_context()->userCan('create_purchase')
            && $bill->status->isEditable();
    }

    public function reverse(User $user, PurchaseBill $bill): bool
    {
        return $user->belongsToBusiness($bill->business)
            && business_context()->userCan('reverse_purchase');
    }

    public function cancel(User $user, PurchaseBill $bill): bool
    {
        return $user->belongsToBusiness($bill->business)
            && business_context()->userCan('cancel_purchase')
            && $bill->status->isConfirmed();
    }
}
