<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\User;
use App\Support\Money;

test('customers index shows a delete action with confirmation and csrf fields', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $customer = Party::factory()->customer()->forBusiness($business)->create(['name' => 'Acme Retail']);

    $this->actingAs($user)
        ->get(route('masters.customers.index'))
        ->assertOk()
        ->assertSee('Acme Retail', false)
        ->assertSee('Delete', false)
        ->assertSee('Are you sure you want to delete this customer?', false)
        ->assertSee('name="_method"', false)
        ->assertSee('name="_token"', false);
});

test('delete customer with no transactions soft deletes it and updates the list', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $customer = Party::factory()->customer()->forBusiness($business)->create(['name' => 'Acme Retail']);

    $this->actingAs($user)
        ->delete(route('masters.customers.destroy', $customer))
        ->assertRedirect(route('masters.customers.index'))
        ->assertSessionHas('status', 'Customer deleted successfully.');

    $this->assertSoftDeleted('parties', ['id' => $customer->id]);
    expect(Party::query()->whereKey($customer->id)->exists())->toBeFalse();
    expect(Party::query()->withTrashed()->whereKey($customer->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->get(route('masters.customers.index'))
        ->assertOk()
        ->assertDontSee('Acme Retail');
});

test('customer with an invoice cannot be deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create(['name' => 'Invoice Customer']);

    app(SalesInvoiceService::class)->confirm(
        app(SalesInvoiceService::class)->createDraft(
            business: $business,
            financialYear: $financialYear,
            user: $user,
            attributes: [
                'party_id' => $customer->id,
                'invoice_date' => now()->toDateString(),
                'settlement_mode' => SettlementMode::Credit,
            ],
            lines: [
                ['description' => 'Branded item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 18],
            ],
        ),
        $user,
    );

    $this->actingAs($user)
        ->delete(route('masters.customers.destroy', $customer))
        ->assertRedirect(route('masters.customers.index'))
        ->assertSessionHas('error', 'This customer cannot be deleted because it has existing transactions.');

    $this->assertNotSoftDeleted('parties', ['id' => $customer->id]);
    expect(Party::query()->whereKey($customer->id)->exists())->toBeTrue();
});

test('a customer with a draft invoice cannot be deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 18],
        ],
    );

    $this->actingAs($user)
        ->delete(route('masters.customers.destroy', $customer))
        ->assertRedirect(route('masters.customers.index'))
        ->assertSessionHas('error', 'This customer cannot be deleted because it has existing transactions.');

    $this->assertNotSoftDeleted('parties', ['id' => $customer->id]);
});

test('delete route rejects supplier records', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    $this->actingAs($user)
        ->delete(route('masters.customers.destroy', $supplier))
        ->assertNotFound();

    $this->assertNotSoftDeleted('parties', ['id' => $supplier->id]);
});

test('user without manage_customers permission cannot delete a customer', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $cashier = User::factory()->create(['email' => 'cashier@test.com']);
    $this->attachUserToBusiness($cashier, $business, SystemRoles::CASHIER);
    app(BusinessContext::class)->setBusiness($business, $cashier);

    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $this->actingAs($cashier)
        ->delete(route('masters.customers.destroy', $customer))
        ->assertForbidden();

    $this->assertNotSoftDeleted('parties', ['id' => $customer->id]);
});

test('a customer belonging to another business cannot be deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $otherOwner = User::factory()->create(['email' => 'other@test.com']);
    $otherBusiness = app(BusinessService::class)->createForOwner($otherOwner, [
        'name' => 'Other Business',
        'state' => 'Delhi',
    ]);

    $foreignCustomer = Party::factory()->customer()->forBusiness($otherBusiness)->create(['name' => 'Foreign Customer']);

    $this->actingAs($user)
        ->delete(route('masters.customers.destroy', $foreignCustomer))
        ->assertNotFound();

    $this->assertNotSoftDeleted('parties', ['id' => $foreignCustomer->id]);
});

test('delete route is part of the web middleware group for csrf protection', function () {
    $route = Route::getRoutes()->getByName('masters.customers.destroy');

    expect($route)->not->toBeNull()
        ->and(in_array('web', $route->gatherMiddleware(), true))->toBeTrue()
        ->and($route->methods())->toContain('DELETE');
});
