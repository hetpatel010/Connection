<?php

namespace Tests;

use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Domain\Authorization\SystemRoles;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

trait CreatesBusinessContext
{
    protected function seedAuthorization(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    protected function createUserWithBusiness(array $userAttributes = [], array $businessAttributes = []): array
    {
        $this->seedAuthorization();

        $user = User::factory()->create($userAttributes);
        $business = app(BusinessService::class)->createForOwner($user, array_merge([
            'name' => 'Test Business',
            'state' => 'Gujarat',
        ], $businessAttributes));

        app(BusinessContext::class)->setBusiness($business, $user);

        return compact('user', 'business');
    }

    protected function attachUserToBusiness(User $user, Business $business, string $roleSlug = SystemRoles::SALESPERSON): BusinessUser
    {
        $role = Role::query()->whereNull('business_id')->where('slug', $roleSlug)->firstOrFail();

        return BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role_id' => $role->id,
            'is_owner' => false,
            'joined_at' => now(),
        ]);
    }
}
