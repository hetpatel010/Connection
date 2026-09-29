<?php

namespace Database\Seeders;

use App\Application\Services\BusinessService;
use App\Application\Services\ChartOfAccountsService;
use App\Application\Services\UnitService;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'owner@demo.test')->first();

        if ($owner === null) {
            $owner = User::factory()->create([
                'name' => 'Demo Owner',
                'email' => 'owner@demo.test',
                'password' => 'password',
            ]);
        }

        if ($owner->email_verified_at === null) {
            $owner->forceFill(['email_verified_at' => now()])->save();
        }

        if ($owner->businesses()->exists()) {
            $business = $owner->businesses()->first();
            $this->ensureBusinessSetup($business, $owner);
            $this->call(DemoSampleDataSeeder::class);

            return;
        }

        $business = app(BusinessService::class)->createForOwner($owner, [
            'name' => 'Demo Traders Pvt Ltd',
            'legal_name' => 'Demo Traders Private Limited',
            'state' => 'Gujarat',
            'is_demo' => true,
        ]);

        $this->ensureBusinessSetup($business, $owner);
        $this->call(DemoSampleDataSeeder::class);
    }

    private function ensureBusinessSetup(Business $business, User $owner): void
    {
        $financialYear = FinancialYear::query()
            ->withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_current', true)
            ->first();

        if ($financialYear === null) {
            return;
        }

        app(ChartOfAccountsService::class)->seedDefaultForBusiness($business, $financialYear, $owner);
        app(UnitService::class)->seedDefaultForBusiness($business);
    }
}
