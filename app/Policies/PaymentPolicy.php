<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('view_payment');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->belongsToBusiness($payment->business)
            && business_context()->userCan('view_payment');
    }

    public function create(User $user): bool
    {
        return business_context()->userCan('create_payment');
    }

    public function reverse(User $user, Payment $payment): bool
    {
        return $user->belongsToBusiness($payment->business)
            && business_context()->userCan('reverse_payment');
    }
}
