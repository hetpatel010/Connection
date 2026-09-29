<?php

namespace App\Application\Services;

use App\Models\Business;
use App\Models\SalesInvoice;
use App\Models\User;
use Database\Seeders\DemoSampleDataSeeder;

class OnboardingSampleDataService
{
    public function __construct(
        private BusinessContext $businessContext,
    ) {}

    public function seedIfEmpty(Business $business, User $user): void
    {
        if (SalesInvoice::query()->withoutGlobalScopes()->where('business_id', $business->id)->exists()) {
            return;
        }

        $this->businessContext->setBusiness($business, $user);

        app(DemoSampleDataSeeder::class)->run();
    }
}
