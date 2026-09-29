<?php

use App\Application\Services\BusinessRoleService;
use App\Application\Services\BusinessUserService;
use App\Domain\Authorization\Permissions;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Shared\Exceptions\CannotModifySystemRoleException;
use App\Domain\Shared\Exceptions\RoleInUseException;
use App\Livewire\Settings\RolesIndex;
use App\Models\Role;
use App\Models\User;
use Livewire\Livewire;

test('owner can create a custom role with selected permissions', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $role = app(BusinessRoleService::class)->create(
        business: $business,
        name: 'Billing Clerk',
        slug: 'billing-clerk',
        description: 'Handles invoices only',
        permissionNames: [
            Permissions::VIEW_DASHBOARD,
            Permissions::VIEW_INVOICE,
            Permissions::CREATE_INVOICE,
        ],
    );

    expect($role->business_id)->toBe($business->id)
        ->and($role->is_system)->toBeFalse()
        ->and($role->permissions->pluck('name')->all())->toContain(Permissions::VIEW_INVOICE)
        ->and($role->permissions->pluck('name')->all())->not->toContain(Permissions::MANAGE_USERS);
});

test('custom role can be assigned to a team member', function () {
    ['business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $role = app(BusinessRoleService::class)->create(
        business: $business,
        name: 'Invoice Viewer',
        slug: 'invoice-viewer',
        description: null,
        permissionNames: [Permissions::VIEW_INVOICE],
    );

    $membership = app(BusinessUserService::class)->addMember(
        business: $business,
        name: 'Viewer User',
        email: 'viewer@example.com',
        password: 'password123',
        roleSlug: $role->slug,
    );

    expect($membership->role->slug)->toBe('invoice-viewer');

    $viewer = User::query()->where('email', 'viewer@example.com')->firstOrFail();
    expect($viewer->hasPermissionInBusiness(Permissions::VIEW_INVOICE, $business))->toBeTrue()
        ->and($viewer->hasPermissionInBusiness(Permissions::CREATE_INVOICE, $business))->toBeFalse();
});

test('custom role permissions can be updated', function () {
    ['business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);
    $service = app(BusinessRoleService::class);

    $role = $service->create(
        business: $business,
        name: 'Ops Staff',
        slug: 'ops-staff',
        description: 'Operations',
        permissionNames: [Permissions::VIEW_INVOICE],
    );

    $updated = $service->update(
        business: $business,
        role: $role,
        name: 'Ops Lead',
        description: 'Operations lead',
        permissionNames: [Permissions::VIEW_INVOICE, Permissions::CREATE_INVOICE],
    );

    expect($updated->name)->toBe('Ops Lead')
        ->and($updated->permissions->pluck('name')->all())->toContain(Permissions::CREATE_INVOICE);
});

test('custom role in use cannot be deleted', function () {
    ['business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);
    $service = app(BusinessRoleService::class);

    $role = $service->create(
        business: $business,
        name: 'Assigned Role',
        slug: 'assigned-role',
        description: null,
        permissionNames: [Permissions::VIEW_INVOICE],
    );

    app(BusinessUserService::class)->addMember(
        business: $business,
        name: 'Member',
        email: 'member@example.com',
        password: 'password123',
        roleSlug: $role->slug,
    );

    expect(fn () => $service->delete($business, $role))
        ->toThrow(RoleInUseException::class);
});

test('unused custom role can be deleted', function () {
    ['business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);
    $service = app(BusinessRoleService::class);

    $role = $service->create(
        business: $business,
        name: 'Temporary Role',
        slug: 'temporary-role',
        description: null,
        permissionNames: [Permissions::VIEW_EXPENSE],
    );

    $service->delete($business, $role);

    expect(Role::query()->find($role->id))->toBeNull();
});

test('system roles cannot be modified through the custom role service', function () {
    ['business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $systemRole = Role::query()->whereNull('business_id')->where('slug', SystemRoles::SALESPERSON)->firstOrFail();

    expect(fn () => app(BusinessRoleService::class)->update(
        business: $business,
        role: $systemRole,
        name: 'Changed',
        description: null,
        permissionNames: [Permissions::VIEW_INVOICE],
    ))->toThrow(CannotModifySystemRoleException::class);
});

test('roles page requires manage_roles permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('settings.roles'))
        ->assertOk();

    $salesperson = User::factory()->create(['email' => 'sales@test.com']);
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('settings.roles'))
        ->assertForbidden();
});

test('owner can create custom role from livewire ui', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    Livewire::actingAs($owner)
        ->test(RolesIndex::class)
        ->call('startCreate')
        ->set('name', 'Store Keeper')
        ->set('description', 'Inventory access')
        ->set('selectedPermissions', [Permissions::VIEW_PRODUCTS, Permissions::MANAGE_INVENTORY])
        ->call('save')
        ->assertHasNoErrors();

    $role = Role::query()
        ->where('business_id', $business->id)
        ->where('slug', 'store-keeper')
        ->first();

    expect($role)->not->toBeNull()
        ->and($role->permissions->pluck('name')->all())->toContain(Permissions::MANAGE_INVENTORY);
});
