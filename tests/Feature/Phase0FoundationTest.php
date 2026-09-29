<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Application\Services\FinancialYearService;
use App\Domain\Authorization\Permissions;
use App\Domain\Authorization\SystemRoles;
use App\Domain\FinancialYear\Enums\FinancialYearStatus;
use App\Domain\Shared\Exceptions\FinancialYearClosedException;
use App\Models\FinancialYear;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Gate;

test('guests are redirected to login when accessing dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('authenticated user without a business is redirected to onboarding', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('onboarding.business'));
});

test('user can create a business and access dashboard', function () {
    $this->seed(PermissionSeeder::class);
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();

    $business = app(BusinessService::class)->createForOwner($user, [
        'name' => 'Acme Traders',
        'state' => 'Maharashtra',
    ]);

    app(BusinessContext::class)->setBusiness($business, $user);

    expect($user->fresh()->businesses)->toHaveCount(1);
    expect(FinancialYear::query()->withoutGlobalScopes()->count())->toBe(1);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});

test('users cannot access another business through session manipulation', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get('/dashboard')
        ->assertRedirect(route('onboarding.business'));
});

test('owner has full permissions in their business', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $this->actingAs($user);

    expect($user->hasPermissionInBusiness(Permissions::MANAGE_USERS, $business))->toBeTrue();
    expect($user->hasPermissionInBusiness(Permissions::CLOSE_FINANCIAL_YEAR, $business))->toBeTrue();
    expect(Gate::allows(Permissions::VIEW_DASHBOARD))->toBeTrue();
});

test('salesperson cannot manage users', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness();
    $salesperson = User::factory()->create();
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    app(BusinessContext::class)->setBusiness($business, $salesperson);

    $this->actingAs($salesperson);

    expect($salesperson->hasPermissionInBusiness(Permissions::MANAGE_USERS, $business))->toBeFalse();
    expect(Gate::allows(Permissions::CREATE_INVOICE))->toBeTrue();
    expect(Gate::allows(Permissions::MANAGE_USERS))->toBeFalse();
});

test('financial year service creates indian april to march year', function () {
    $this->travelTo('2025-08-15');

    ['business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->first();

    expect($financialYear)->not->toBeNull();
    expect($financialYear->label)->toBe('2025-26');
    expect($financialYear->starts_on->toDateString())->toBe('2025-04-01');
    expect($financialYear->ends_on->toDateString())->toBe('2026-03-31');
    expect($financialYear->status)->toBe(FinancialYearStatus::Open);
});

test('closed financial year cannot be modified through service guard', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->firstOrFail();

    app(FinancialYearService::class)->close($financialYear, $user);

    expect(fn () => app(FinancialYearService::class)->assertWritable($financialYear->fresh()))
        ->toThrow(FinancialYearClosedException::class);
});

test('business scoped queries only return records for current business', function () {
    ['user' => $user, 'business' => $firstBusiness] = $this->createUserWithBusiness(['email' => 'owner1@test.com']);

    $secondOwner = User::factory()->create(['email' => 'owner2@test.com']);
    app(BusinessService::class)->createForOwner($secondOwner, [
        'name' => 'Second Business',
        'state' => 'Delhi',
    ]);

    app(BusinessContext::class)->setBusiness($firstBusiness, $user);

    expect(FinancialYear::query()->count())->toBe(1);
    expect(FinancialYear::query()->first()->business_id)->toBe($firstBusiness->id);
});
