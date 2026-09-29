<?php

namespace App\Application\Services;

use App\Domain\Authorization\SystemRoles;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusinessService
{
    public function __construct(
        private FinancialYearService $financialYearService,
        private ChartOfAccountsService $chartOfAccountsService,
        private UnitService $unitService,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createForOwner(User $owner, array $attributes): Business
    {
        return DB::transaction(function () use ($owner, $attributes): Business {
            $business = Business::query()->create([
                'name' => $attributes['name'],
                'legal_name' => $attributes['legal_name'] ?? $attributes['name'],
                'slug' => $attributes['slug'] ?? Str::slug($attributes['name']).'-'.Str::lower(Str::random(6)),
                'state' => $attributes['state'] ?? null,
                'country' => $attributes['country'] ?? 'IN',
                'currency_code' => $attributes['currency_code'] ?? 'INR',
                'timezone' => $attributes['timezone'] ?? 'Asia/Kolkata',
                'is_demo' => $attributes['is_demo'] ?? false,
            ]);

            $ownerRole = Role::query()
                ->whereNull('business_id')
                ->where('slug', SystemRoles::OWNER)
                ->firstOrFail();

            BusinessUser::query()->create([
                'business_id' => $business->id,
                'user_id' => $owner->id,
                'role_id' => $ownerRole->id,
                'is_owner' => true,
                'joined_at' => now(),
            ]);

            $financialYear = $this->financialYearService->createCurrentIndianFinancialYear($business);

            $this->chartOfAccountsService->seedDefaultForBusiness($business, $financialYear, $owner);

            $this->unitService->seedDefaultForBusiness($business);

            return $business->fresh(['financialYears']);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateProfile(Business $business, array $attributes): Business
    {
        $business->update([
            'name' => $attributes['name'],
            'legal_name' => $attributes['legal_name'] ?? $attributes['name'],
            'email' => $attributes['email'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'gstin' => $attributes['gstin'] ?? null,
            'pan' => $attributes['pan'] ?? null,
            'address_line_1' => $attributes['address_line_1'] ?? null,
            'address_line_2' => $attributes['address_line_2'] ?? null,
            'city' => $attributes['city'] ?? null,
            'state' => $attributes['state'] ?? null,
            'country' => $attributes['country'] ?? 'IN',
            'postal_code' => $attributes['postal_code'] ?? null,
            'timezone' => $attributes['timezone'] ?? 'Asia/Kolkata',
            'currency_code' => $attributes['currency_code'] ?? 'INR',
        ]);

        return $business->fresh();
    }
}
