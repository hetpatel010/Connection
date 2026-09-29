<?php

namespace App\Policies;

use App\Models\SalesInvoice;
use App\Models\User;

class SalesInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return business_context()->userCan('view_invoice');
    }

    public function view(User $user, SalesInvoice $invoice): bool
    {
        return $user->belongsToBusiness($invoice->business)
            && business_context()->userCan('view_invoice');
    }

    public function create(User $user): bool
    {
        return business_context()->userCan('create_invoice');
    }

    public function update(User $user, SalesInvoice $invoice): bool
    {
        return $user->belongsToBusiness($invoice->business)
            && business_context()->userCan('edit_invoice')
            && $invoice->status->isEditable();
    }

    public function delete(User $user, SalesInvoice $invoice): bool
    {
        return $user->belongsToBusiness($invoice->business)
            && business_context()->userCan('delete_invoice')
            && $invoice->status->isEditable();
    }

    public function confirm(User $user, SalesInvoice $invoice): bool
    {
        return $user->belongsToBusiness($invoice->business)
            && business_context()->userCan('create_invoice')
            && $invoice->status->isEditable();
    }

    public function reverse(User $user, SalesInvoice $invoice): bool
    {
        return $user->belongsToBusiness($invoice->business)
            && business_context()->userCan('reverse_invoice');
    }

    public function cancel(User $user, SalesInvoice $invoice): bool
    {
        return $user->belongsToBusiness($invoice->business)
            && business_context()->userCan('cancel_invoice')
            && $invoice->status->isConfirmed();
    }
}
