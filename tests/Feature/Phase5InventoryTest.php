<?php

use App\Application\Services\InventoryService;
use App\Application\Services\PurchaseBillService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Inventory\Enums\StockDirection;
use App\Domain\Shared\Exceptions\InsufficientStockException;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Support\Money;

test('current stock includes opening stock and movements', function () {
    ['business' => $business] = $this->createUserWithBusiness();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'opening_stock' => 100,
    ]);

    expect(app(InventoryService::class)->currentStock($product))->toBe(100.0);
});

test('confirmed sales invoice reduces stock', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'opening_stock' => 50,
        'selling_price' => Money::rupeesToPaise(100),
    ]);

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
            ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    );

    app(SalesInvoiceService::class)->confirm($invoice, $user);

    expect(app(InventoryService::class)->currentStock($product->fresh()))->toBe(40.0);
    expect(StockMovement::query()->where('product_id', $product->id)->count())->toBe(1);
});

test('confirmed purchase bill increases stock', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $supplier = Party::factory()->supplier()->forBusiness($business)->create();

    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'opening_stock' => 10,
        'purchase_price' => Money::rupeesToPaise(50),
    ]);

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
            ['product_id' => $product->id, 'quantity' => 25, 'unit_price' => Money::rupeesToPaise(50), 'tax_rate' => 0],
        ],
    );

    app(PurchaseBillService::class)->confirm($bill, $user);

    expect(app(InventoryService::class)->currentStock($product->fresh()))->toBe(35.0);
});

test('sales invoice is rejected when stock is insufficient', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'opening_stock' => 5,
    ]);

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
            ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    );

    expect(fn () => app(SalesInvoiceService::class)->confirm($invoice, $user))
        ->toThrow(InsufficientStockException::class);

    expect(app(InventoryService::class)->currentStock($product))->toBe(5.0);
});

test('reversing sales invoice restores stock', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'opening_stock' => 20,
    ]);

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
            ['product_id' => $product->id, 'quantity' => 8, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);
    app(SalesInvoiceService::class)->reverse($invoice, $user);

    expect(app(InventoryService::class)->currentStock($product->fresh()))->toBe(20.0);
});

test('manual stock adjustment updates quantity', function () {
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

    expect(app(InventoryService::class)->currentStock($product->fresh()))->toBe(15.0);
});

test('inventory page requires manage_inventory permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('inventory.index'))
        ->assertOk();

    $salesperson = User::factory()->create(['email' => 'sales@test.com']);
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('inventory.index'))
        ->assertForbidden();
});
