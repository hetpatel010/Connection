<?php

namespace App\Application\Services;

use App\Models\Account;
use App\Models\Business;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AccountLookupService
{
    public function byCode(Business $business, string $code): Account
    {
        $account = $this->findByCode($business, $code);

        if ($account === null) {
            throw (new ModelNotFoundException)->setModel(Account::class);
        }

        return $account;
    }

    public function findByCode(Business $business, string $code): ?Account
    {
        return Account::query()
            ->where('business_id', $business->id)
            ->where('code', $code)
            ->first();
    }
}
