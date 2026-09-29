<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Application\Services\PartyService;
use App\Application\Services\ProductService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Products\DefaultUnits;
use App\Models\Party;
use App\Models\Unit;
use App\Models\User;

test('default units are seeded when a business is created', function () {
    ['business' => $business] = $this->createUserWithBusiness();

    expect(Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->count())
        ->toBe(count(DefaultUnits::definitions()));

    $defaultUnit = Unit::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->where('is_default', true)
        ->first();

    expect($defaultUnit?->symbol)->toBe('Pcs');
});

test('party service creates customers scoped to business', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $party = app(PartyService::class)->create($business, [
        'party_type' => 'customer',
        'name' => 'Acme Retail',
        'phone' => '9876543210',
    ]);

    expect($party->business_id)->toBe($business->id);
    expect($party->party_type->value)->toBe('customer');
    expect($party->name)->toBe('Acme Retail');
});

test('product service creates products with money stored in paise', function () {
    ['business' => $business] = $this->createUserWithBusiness();

    $unit = Unit::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->where('symbol', 'Pcs')
        ->firstOrFail();

    $product = app(ProductService::class)->create($business, [
        'name' => 'Widget Pro',
        'unit_id' => $unit->id,
        'selling_price' => 499.99,
        'purchase_price' => 350,
    ]);

    expect($product->business_id)->toBe($business->id);
    expect($product->selling_price)->toBe(49999);
    expect($product->purchase_price)->toBe(35000);
});

test('masters records are isolated between businesses', function () {
    ['user' => $user, 'business' => $firstBusiness] = $this->createUserWithBusiness(['email' => 'owner1@test.com']);

    $secondOwner = User::factory()->create(['email' => 'owner2@test.com']);
    $secondBusiness = app(BusinessService::class)->createForOwner($secondOwner, [
        'name' => 'Second Business',
        'state' => 'Delhi',
    ]);

    Party::factory()->customer()->forBusiness($firstBusiness)->create(['name' => 'First Customer']);
    Party::factory()->customer()->forBusiness($secondBusiness)->create(['name' => 'Second Customer']);

    app(BusinessContext::class)->setBusiness($firstBusiness, $user);

    expect(Party::query()->count())->toBe(1);
    expect(Party::query()->first()->name)->toBe('First Customer');
});

test('owner can access masters pages', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    $this->actingAs($user)
        ->get(route('masters.customers.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('masters.suppliers.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('masters.products.index'))
        ->assertOk();
});

test('salesperson can view customers and products but not suppliers', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);
    $salesperson = User::factory()->create(['email' => 'sales@test.com']);
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('masters.customers.index'))
        ->assertOk();

    $this->actingAs($salesperson)
        ->get(route('masters.products.index'))
        ->assertOk();

    $this->actingAs($salesperson)
        ->get(route('masters.suppliers.index'))
        ->assertForbidden();
});

test('salesperson cannot create customers without manage permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);
    $salesperson = User::factory()->create(['email' => 'sales@test.com']);
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('masters.customers.create'))
        ->assertForbidden();
});

test('customer edit route rejects supplier records', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    $this->actingAs($user)
        ->get(route('masters.customers.edit', $supplier))
        ->assertNotFound();
});

test('inventory manager can manage categories and units', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);
    $inventoryManager = User::factory()->create(['email' => 'inventory@test.com']);
    $this->attachUserToBusiness($inventoryManager, $business, SystemRoles::INVENTORY_MANAGER);

    $this->actingAs($inventoryManager)
        ->get(route('masters.categories.index'))
        ->assertOk();

    $this->actingAs($inventoryManager)
        ->get(route('masters.units.index'))
        ->assertOk();
});
