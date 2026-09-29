<?php

namespace Tests;

use App\Models\Account;
use App\Models\Business;

trait CreatesAccountingContext
{
    protected function accountByCode(Business $business, string $code): Account
    {
        return Account::query()
            ->withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
