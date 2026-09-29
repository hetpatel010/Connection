<?php

use App\Application\Services\BusinessUserService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Shared\Exceptions\CannotModifyOwnerMembershipException;
use App\Domain\Shared\Exceptions\UserAlreadyMemberException;
use App\Models\BusinessUser;
use App\Models\User;

test('owner can add a new user to the business', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $membership = app(BusinessUserService::class)->addMember(
        business: $business,
        name: 'Sales Rep',
        email: 'sales@example.com',
        password: 'password123',
        roleSlug: SystemRoles::SALESPERSON,
    );

    expect($membership->role->slug)->toBe(SystemRoles::SALESPERSON);
    expect(User::query()->where('email', 'sales@example.com')->exists())->toBeTrue();
    expect(BusinessUser::query()->where('business_id', $business->id)->count())->toBe(2);
});

test('adding an existing user attaches them without creating duplicate account', function () {
    ['business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $existing = User::factory()->create(['email' => 'existing@test.com']);

    app(BusinessUserService::class)->addMember(
        business: $business,
        name: 'Existing User',
        email: 'existing@test.com',
        password: 'password123',
        roleSlug: SystemRoles::ACCOUNTANT,
    );

    expect(User::query()->where('email', 'existing@test.com')->count())->toBe(1);
    expect($existing->fresh()->belongsToBusiness($business))->toBeTrue();
});

test('duplicate business membership is rejected', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    expect(fn () => app(BusinessUserService::class)->addMember(
        business: $business,
        name: $owner->name,
        email: $owner->email,
        password: 'password123',
        roleSlug: SystemRoles::SALESPERSON,
    ))->toThrow(UserAlreadyMemberException::class);
});

test('member role can be updated but owner role is protected', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $service = app(BusinessUserService::class);

    $membership = $service->addMember(
        business: $business,
        name: 'Cashier User',
        email: 'cashier@example.com',
        password: 'password123',
        roleSlug: SystemRoles::CASHIER,
    );

    $updated = $service->updateRole($business, $membership, SystemRoles::MANAGER);
    expect($updated->role->slug)->toBe(SystemRoles::MANAGER);

    $ownerMembership = BusinessUser::query()
        ->where('business_id', $business->id)
        ->where('is_owner', true)
        ->firstOrFail();

    expect(fn () => $service->updateRole($business, $ownerMembership, SystemRoles::MANAGER))
        ->toThrow(CannotModifyOwnerMembershipException::class);
});

test('non-owner members can be removed except by themselves', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $service = app(BusinessUserService::class);

    $membership = $service->addMember(
        business: $business,
        name: 'Temp User',
        email: 'temp@example.com',
        password: 'password123',
        roleSlug: SystemRoles::SALESPERSON,
    );

    $service->removeMember($business, $membership, $owner);

    expect(BusinessUser::query()->find($membership->id))->toBeNull();

    $selfMembership = $service->addMember(
        business: $business,
        name: 'Self Remove',
        email: 'self@example.com',
        password: 'password123',
        roleSlug: SystemRoles::SALESPERSON,
    );

    $selfUser = User::query()->where('email', 'self@example.com')->firstOrFail();

    expect(fn () => $service->removeMember($business, $selfMembership, $selfUser))
        ->toThrow(CannotModifyOwnerMembershipException::class);
});

test('team members page requires manage_users permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('settings.users'))
        ->assertOk();

    $salesperson = User::factory()->create(['email' => 'sales@test.com']);
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('settings.users'))
        ->assertForbidden();
});
