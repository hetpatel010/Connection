<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Application\Services\PurchaseBillService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\PurchaseBill;
use App\Models\User;
use App\Support\Money;

test('suppliers index shows a delete action with confirmation and csrf fields', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $supplier = Party::factory()->supplier()->forBusiness($business)->create(['name' => 'Acme Traders']);

    $this->actingAs($user)
        ->get(route('masters.suppliers.index'))
        ->assertOk()
        ->assertSee('Acme Traders', false)
        ->assertSee('Delete', false)
        ->assertSee('Are you sure you want to delete this supplier?', false)
        ->assertSee('name="_method"', false)
        ->assertSee('name="_token"', false);
});

test('edit action still works alongside delete', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    $this->actingAs($user)
        ->get(route('masters.suppliers.index'))
        ->assertOk()
        ->assertSee('Edit', false);

    $this->actingAs($user)
        ->get(route('masters.suppliers.edit', $supplier))
        ->assertOk();
});

test('cancelling the confirmation leaves the supplier unchanged', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $supplier = Party::factory()->supplier()->forBusiness($business)->create(['name' => 'Keep Me Supplier']);

    $this->actingAs($user)
        ->get(route('masters.suppliers.index'))
        ->assertOk()
        ->assertSee('Keep Me Supplier', false)
        ->assertSee('Cancel', false);

    expect(Party::query()->whereKey($supplier->id)->exists())->toBeTrue();
});

test('delete supplier with no transactions soft deletes it and updates the list', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $supplier = Party::factory()->supplier()->forBusiness($business)->create(['name' => 'Acme Traders']);

    $this->actingAs($user)
        ->delete(route('masters.suppliers.destroy', $supplier))
        ->assertRedirect(route('masters.suppliers.index'))
        ->assertSessionHas('status', 'Supplier deleted successfully.');

    $this->assertSoftDeleted('parties', ['id' => $supplier->id]);
    expect(Party::query()->whereKey($supplier->id)->exists())->toBeFalse();
    expect(Party::query()->withTrashed()->whereKey($supplier->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->get(route('masters.suppliers.index'))
        ->assertOk()
        ->assertDontSee('Acme Traders');
});

test('supplier with a purchase bill cannot be deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create(['name' => 'Billed Supplier']);

    $bill = app(PurchaseBillService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $supplier->id,
            'bill_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Raw material', 'quantity' => 5, 'unit_price' => Money::rupeesToPaise(200), 'tax_rate' => 0],
        ],
    );

    app(PurchaseBillService::class)->confirm($bill, $user);

    $this->actingAs($user)
        ->delete(route('masters.suppliers.destroy', $supplier))
        ->assertRedirect(route('masters.suppliers.index'))
        ->assertSessionHas('error', 'This supplier cannot be deleted because it has existing transactions.');

    $this->assertNotSoftDeleted('parties', ['id' => $supplier->id]);
    expect(Party::query()->whereKey($supplier->id)->exists())->toBeTrue();
    expect(PurchaseBill::query()->where('party_id', $supplier->id)->exists())->toBeTrue();
});

test('supplier with a draft purchase bill cannot be deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    app(PurchaseBillService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $supplier->id,
            'bill_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Pending supplies', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    );

    $this->actingAs($user)
        ->delete(route('masters.suppliers.destroy', $supplier))
        ->assertRedirect(route('masters.suppliers.index'))
        ->assertSessionHas('error', 'This supplier cannot be deleted because it has existing transactions.');

    $this->assertNotSoftDeleted('parties', ['id' => $supplier->id]);
});

test('delete route rejects customer records', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $this->actingAs($user)
        ->delete(route('masters.suppliers.destroy', $customer))
        ->assertNotFound();

    $this->assertNotSoftDeleted('parties', ['id' => $customer->id]);
});

test('user without manage_suppliers permission cannot delete a supplier', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $cashier = User::factory()->create(['email' => 'cashier@test.com']);
    $this->attachUserToBusiness($cashier, $business, SystemRoles::CASHIER);
    app(BusinessContext::class)->setBusiness($business, $cashier);

    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    $this->actingAs($cashier)
        ->delete(route('masters.suppliers.destroy', $supplier))
        ->assertForbidden();

    $this->assertNotSoftDeleted('parties', ['id' => $supplier->id]);
});

test('a supplier belonging to another business cannot be deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $otherOwner = User::factory()->create(['email' => 'other@test.com']);
    $otherBusiness = app(BusinessService::class)->createForOwner($otherOwner, [
        'name' => 'Other Business',
        'state' => 'Delhi',
    ]);

    $foreignSupplier = Party::factory()->supplier()->forBusiness($otherBusiness)->create(['name' => 'Foreign Supplier']);

    $this->actingAs($user)
        ->delete(route('masters.suppliers.destroy', $foreignSupplier))
        ->assertNotFound();

    $this->assertNotSoftDeleted('parties', ['id' => $foreignSupplier->id]);
});

test('delete route is part of the web middleware group for csrf protection', function () {
    $route = Route::getRoutes()->getByName('masters.suppliers.destroy');

    expect($route)->not->toBeNull()
        ->and(in_array('web', $route->gatherMiddleware(), true))->toBeTrue()
        ->and($route->methods())->toContain('DELETE');
});
