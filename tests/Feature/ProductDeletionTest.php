<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Application\Services\InventoryService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Inventory\Enums\StockDirection;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\SalesInvoiceLine;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Support\Money;

test('products index shows a delete action with confirmation and csrf fields', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $product = Product::factory()->forBusiness($business)->create(['name' => 'Premium Widget']);

    $this->actingAs($user)
        ->get(route('masters.products.index'))
        ->assertOk()
        ->assertSee('Premium Widget', false)
        ->assertSee('Delete', false)
        ->assertSee('Are you sure you want to delete this product?', false)
        ->assertSee('name="_method"', false)
        ->assertSee('name="_token"', false);
});

test('edit action still works alongside delete', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $product = Product::factory()->forBusiness($business)->create();

    $this->actingAs($user)
        ->get(route('masters.products.index'))
        ->assertOk()
        ->assertSee('Edit', false);

    $this->actingAs($user)
        ->get(route('masters.products.edit', $product))
        ->assertOk();
});

test('cancelling the confirmation leaves the product unchanged', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $product = Product::factory()->forBusiness($business)->create(['name' => 'Keep Me Widget']);

    $this->actingAs($user)
        ->get(route('masters.products.index'))
        ->assertOk()
        ->assertSee('Keep Me Widget', false)
        ->assertSee('Cancel', false);

    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();
});

test('delete product with no references soft deletes it and updates the list', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $product = Product::factory()->forBusiness($business)->create(['name' => 'Premium Widget']);

    $this->actingAs($user)
        ->delete(route('masters.products.destroy', $product))
        ->assertRedirect(route('masters.products.index'))
        ->assertSessionHas('status', 'Product deleted successfully.');

    $this->assertSoftDeleted('products', ['id' => $product->id]);
    expect(Product::query()->whereKey($product->id)->exists())->toBeFalse();
    expect(Product::query()->withTrashed()->whereKey($product->id)->exists())->toBeTrue();

    $this->actingAs($user)
        ->get(route('masters.products.index'))
        ->assertOk()
        ->assertDontSee('Premium Widget');
});

test('product used in a confirmed invoice cannot be deleted and history is preserved', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = Product::factory()->forBusiness($business)->create(['name' => 'Invoiced Widget', 'opening_stock' => 100]);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
        lines: [
            ['product_id' => $product->id, 'description' => 'Branded item', 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 18],
        ],
    );

    app(SalesInvoiceService::class)->confirm($invoice, $user);

    $this->actingAs($user)
        ->delete(route('masters.products.destroy', $product))
        ->assertRedirect(route('masters.products.index'))
        ->assertSessionHas('error', 'This product cannot be deleted because it is used in invoices, purchases or stock movements.');

    $this->assertNotSoftDeleted('products', ['id' => $product->id]);
    expect(Product::query()->whereKey($product->id)->exists())->toBeTrue();
    expect(SalesInvoiceLine::query()->where('product_id', $product->id)->exists())->toBeTrue();
    expect(StockMovement::query()->where('product_id', $product->id)->exists())->toBeTrue();
});

test('product with a stock movement cannot be deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'opening_stock' => 10,
    ]);

    app(InventoryService::class)->adjust(
        product: $product,
        user: $user,
        quantity: 5,
        direction: StockDirection::In,
        movementDate: now()->toDateString(),
        notes: 'Found extra units',
    );

    $this->actingAs($user)
        ->delete(route('masters.products.destroy', $product))
        ->assertRedirect(route('masters.products.index'))
        ->assertSessionHas('error', 'This product cannot be deleted because it is used in invoices, purchases or stock movements.');

    $this->assertNotSoftDeleted('products', ['id' => $product->id]);
    expect(StockMovement::query()->where('product_id', $product->id)->exists())->toBeTrue();
});

test('user without manage_products permission cannot delete a product', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $cashier = User::factory()->create(['email' => 'cashier@test.com']);
    $this->attachUserToBusiness($cashier, $business, SystemRoles::CASHIER);
    app(BusinessContext::class)->setBusiness($business, $cashier);

    $product = Product::factory()->forBusiness($business)->create();

    $this->actingAs($cashier)
        ->delete(route('masters.products.destroy', $product))
        ->assertForbidden();

    $this->assertNotSoftDeleted('products', ['id' => $product->id]);
});

test('a product belonging to another business cannot be deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $otherOwner = User::factory()->create(['email' => 'other@test.com']);
    $otherBusiness = app(BusinessService::class)->createForOwner($otherOwner, [
        'name' => 'Other Business',
        'state' => 'Delhi',
    ]);

    $foreignProduct = Product::factory()->forBusiness($otherBusiness)->create(['name' => 'Foreign Widget']);

    $this->actingAs($user)
        ->delete(route('masters.products.destroy', $foreignProduct))
        ->assertNotFound();

    $this->assertNotSoftDeleted('products', ['id' => $foreignProduct->id]);
});

test('delete route is part of the web middleware group for csrf protection', function () {
    $route = Route::getRoutes()->getByName('masters.products.destroy');

    expect($route)->not->toBeNull()
        ->and(in_array('web', $route->gatherMiddleware(), true))->toBeTrue()
        ->and($route->methods())->toContain('DELETE');
});
