<?php

use App\Application\Services\BusinessService;
use App\Application\Services\InventoryService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\Inventory\Enums\StockDirection;
use App\Livewire\Inventory\AdjustStock;
use App\Livewire\Inventory\StockLedger;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;

function addStockMovement(
    Product $product,
    User $user,
    string $type,
    string $direction,
    float $quantity,
    string $date,
    ?string $reference = null,
    ?string $notes = null,
): StockMovement {
    return StockMovement::query()->create([
        'business_id' => $product->business_id,
        'product_id' => $product->id,
        'movement_date' => $date,
        'movement_type' => $type,
        'direction' => $direction,
        'quantity' => $quantity,
        'source_type' => 'product',
        'source_id' => $product->id,
        'reference' => $reference,
        'notes' => $notes ?? 'Test movement',
        'created_by' => $user->id,
    ]);
}

function ledgerProduct(array $attributes = []): array
{
    $context = test()->createUserWithBusiness();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $context['business']->id)->firstOrFail();
    $product = Product::factory()->forBusiness($context['business'])->create(array_merge([
        'unit_id' => $unit->id,
        'opening_stock' => 0,
        'name' => 'Test Widget',
        'sku' => 'TW-001',
    ], $attributes));

    return $context + compact('product');
}

test('stock ledger page loads and displays product movements', function () {
    ['user' => $user, 'business' => $business, 'product' => $product] = ledgerProduct(['opening_stock' => 10]);

    app(InventoryService::class)->adjust(
        product: $product,
        user: $user,
        quantity: 5,
        direction: StockDirection::In,
        movementDate: '2026-09-01',
        notes: 'Initial stock in',
    );

    $this->actingAs($user)
        ->get(route('inventory.ledger', $product))
        ->assertOk()
        ->assertSee('15', false)
        ->assertSee('Adjust Stock', false);
});

test('stock ledger shows movements in the correct order', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    app(InventoryService::class)->adjust(
        product: $product,
        user: $user,
        quantity: 20,
        direction: StockDirection::In,
        movementDate: '2026-09-01',
        notes: 'Stock in',
    );

    app(InventoryService::class)->adjust(
        product: $product,
        user: $user,
        quantity: 5,
        direction: StockDirection::Out,
        movementDate: '2026-09-05',
        notes: 'Sale out',
    );

    $this->actingAs($user)
        ->get(route('inventory.ledger', $product))
        ->assertOk()
        ->assertSee('20', false)
        ->assertSee('5', false);
});

test('running balance is calculated correctly from database transactions', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct(['opening_stock' => 100]);

    addStockMovement($product, $user, 'purchase', 'in', 20, '2026-09-01', 'PB-001');
    addStockMovement($product, $user, 'sale', 'out', 15, '2026-09-05', 'INV-001');
    addStockMovement($product, $user, 'adjustment', 'in', 5, '2026-09-10', 'ADJ-001');

    $this->actingAs($user)
        ->get(route('inventory.ledger', $product))
        ->assertOk()
        ->assertSee('120', false)
        ->assertSee('105', false)
        ->assertSee('110', false);

    expect(app(InventoryService::class)->currentStock($product->fresh()))->toBe(110.0);
});

test('product that reaches zero stock still opens ledger with correct balance', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct(['opening_stock' => 10]);

    addStockMovement($product, $user, 'sale', 'out', 10, '2026-09-01', 'INV-001');

    $this->actingAs($user)
        ->get(route('inventory.ledger', $product))
        ->assertOk()
        ->assertSee('0');

    expect(app(InventoryService::class)->currentStock($product->fresh()))->toBe(0.0);
});

test('stock ledger shows empty state for product with no movements', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    $this->actingAs($user)
        ->get(route('inventory.ledger', $product))
        ->assertOk()
        ->assertSee('No stock transactions found', false)
        ->assertSee('This product does not have any stock movement yet.', false)
        ->assertSee('Back to Inventory', false);
});

test('ledger button on inventory page includes the actual product id', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    $this->actingAs($user)
        ->get(route('inventory.index'))
        ->assertOk()
        ->assertSee('/inventory/'.$product->id.'/ledger', false);
});

test('invalid product id returns 404', function () {
    ['user' => $user] = ledgerProduct();

    $this->actingAs($user)
        ->get('/inventory/not-a-real-product/ledger')
        ->assertNotFound();
});

test('ledger page loads successfully for product with zero stock', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct(['opening_stock' => 0]);

    addStockMovement($product, $user, 'purchase', 'in', 5, '2026-09-01', 'PB-002');
    addStockMovement($product, $user, 'sale', 'out', 5, '2026-09-02', 'INV-002');

    $this->actingAs($user)
        ->get(route('inventory.ledger', $product))
        ->assertOk()
        ->assertSee('0');
});

test('from and to date filters limit ledger records', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-01', 'PB-EARLY');
    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-15', 'PB-MIDDLE');
    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-30', 'PB-LATE');

    Livewire::actingAs($user)
        ->test(StockLedger::class, ['product' => $product])
        ->set('fromDate', '2026-09-10')
        ->set('toDate', '2026-09-20')
        ->assertSee('PB-MIDDLE', false)
        ->assertDontSee('PB-EARLY', false)
        ->assertDontSee('PB-LATE', false);
});

test('transaction type filter limits ledger records', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-01', 'PB-001');
    addStockMovement($product, $user, 'sale', 'out', 3, '2026-09-02', 'INV-001');

    Livewire::actingAs($user)
        ->test(StockLedger::class, ['product' => $product])
        ->set('type', 'sale')
        ->assertSee('Sale', false)
        ->assertDontSee('PB-001', false)
        ->assertSee('INV-001', false);
});

test('stock adjustment in and out are distinct filters', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    addStockMovement($product, $user, 'adjustment', 'in', 10, '2026-09-01', 'ADJ-IN-1');
    addStockMovement($product, $user, 'adjustment', 'out', 5, '2026-09-02', 'ADJ-OUT-1');

    Livewire::actingAs($user)
        ->test(StockLedger::class, ['product' => $product])
        ->set('type', 'adjustment:in')
        ->assertSee('Stock Adjustment In', false)
        ->assertSee('ADJ-IN-1', false)
        ->assertDontSee('ADJ-OUT-1', false);
});

test('reference filter limits ledger records', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-01', 'PB-ABC-100');
    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-02', 'PB-XYZ-200');

    Livewire::actingAs($user)
        ->test(StockLedger::class, ['product' => $product])
        ->set('reference', 'ABC')
        ->assertSee('PB-ABC-100', false)
        ->assertDontSee('PB-XYZ-200', false);
});

test('search queries reference, description, and type in the database', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-01', 'PB-001', 'Monthly restock');
    addStockMovement($product, $user, 'sale', 'out', 2, '2026-09-02', 'INV-001', 'Walk-in customer');

    Livewire::actingAs($user)
        ->test(StockLedger::class, ['product' => $product])
        ->set('search', 'restock')
        ->assertSee('PB-001', false)
        ->assertDontSee('INV-001', false);

    Livewire::actingAs($user)
        ->test(StockLedger::class, ['product' => $product])
        ->set('search', 'PB-001')
        ->assertSee('PB-001', false)
        ->assertDontSee('INV-001', false);

    Livewire::actingAs($user)
        ->test(StockLedger::class, ['product' => $product])
        ->set('search', 'sale')
        ->assertSee('INV-001', false)
        ->assertDontSee('PB-001', false);
});

test('resetting filters returns all records', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-01', 'PB-001');
    addStockMovement($product, $user, 'sale', 'out', 2, '2026-09-02', 'INV-001');

    Livewire::actingAs($user)
        ->test(StockLedger::class, ['product' => $product])
        ->set('type', 'sale')
        ->assertDontSee('PB-001', false)
        ->call('resetFilters')
        ->assertSee('PB-001', false)
        ->assertSee('INV-001', false);
});

test('applying filters via url query parameters works', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-01', 'PB-EARLY');
    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-15', 'PB-LATE');

    $this->actingAs($user)
        ->get(route('inventory.ledger', $product).'?from=2026-09-10')
        ->assertOk()
        ->assertSee('PB-LATE', false)
        ->assertDontSee('PB-EARLY', false);
});

test('ledger pagination does not load all transactions at once', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    for ($day = 1; $day <= 30; $day++) {
        addStockMovement($product, $user, 'purchase', 'in', 1, '2026-01-'.str_pad((string) $day, 2, '0', STR_PAD_LEFT), 'PB-'.str_pad((string) $day, 3, '0', STR_PAD_LEFT));
    }

    $this->actingAs($user)
        ->get(route('inventory.ledger', $product).'?page=2')
        ->assertOk()
        ->assertSee('PB-026', false)
        ->assertDontSee('PB-001', false);
});

test('stock adjust page loads and displays correct product', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct([
        'opening_stock' => 10,
        'name' => 'Adjust Widget',
    ]);

    $this->actingAs($user)
        ->get(route('inventory.adjust', $product))
        ->assertOk()
        ->assertSee('Adjust Widget')
        ->assertSee('Save Adjustment', false);
});

test('stock adjustment creates a movement and updates current stock', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct(['opening_stock' => 10]);

    $this->actingAs($user)
        ->get(route('inventory.adjust', $product))
        ->assertOk();

    Livewire::actingAs($user)
        ->test(AdjustStock::class, ['product' => $product])
        ->set('movementDate', now()->toDateString())
        ->set('direction', 'in')
        ->set('quantity', '5')
        ->set('reference', 'ADJ-TEST-001')
        ->set('notes', 'Stock in')
        ->call('save');

    expect(StockMovement::query()->where('product_id', $product->id)->where('quantity', 5)->where('reference', 'ADJ-TEST-001')->exists())->toBeTrue();
    expect(app(InventoryService::class)->currentStock($product->fresh()))->toBe(15.0);
});

test('stock adjustment out reduces stock and records movement', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct(['opening_stock' => 10]);

    Livewire::actingAs($user)
        ->test(AdjustStock::class, ['product' => $product])
        ->set('movementDate', now()->toDateString())
        ->set('direction', 'out')
        ->set('quantity', '4')
        ->set('notes', 'Damaged units')
        ->call('save');

    expect(StockMovement::query()->where('product_id', $product->id)->where('direction', 'out')->where('quantity', 4)->exists())->toBeTrue();
    expect(app(InventoryService::class)->currentStock($product->fresh()))->toBe(6.0);
});

test('ledger excel export downloads actual records', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-01', 'PB-001');
    addStockMovement($product, $user, 'sale', 'out', 4, '2026-09-02', 'INV-001');

    $response = $this->actingAs($user)
        ->get(route('inventory.ledger.export', $product));

    $response->assertOk();
    $response->assertDownload();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
});

test('ledger excel export respects filters', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    addStockMovement($product, $user, 'purchase', 'in', 10, '2026-09-01', 'PB-001');
    addStockMovement($product, $user, 'sale', 'out', 4, '2026-09-02', 'INV-001');

    $this->actingAs($user)
        ->get(route('inventory.ledger.export', $product).'?q=PB-001')
        ->assertOk()
        ->assertDownload();
});

test('ledger export requires manage_inventory permission', function () {
    ['user' => $owner, 'business' => $business, 'product' => $product] = ledgerProduct();

    $salesperson = User::factory()->create();
    test()->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('inventory.ledger.export', $product))
        ->assertForbidden();
});

test('product with no movements has current stock showing opening_stock', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct(['opening_stock' => 25]);

    $this->actingAs($user)
        ->get(route('inventory.ledger', $product))
        ->assertOk()
        ->assertSee('25');
});

test('user without manage_inventory permission is blocked from ledger', function () {
    ['user' => $owner, 'business' => $business, 'product' => $product] = ledgerProduct();

    $salesperson = User::factory()->create();
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('inventory.ledger', $product))
        ->assertForbidden();
});

test('another business product is not accessible', function () {
    ['user' => $user, 'product' => $product] = ledgerProduct();

    $otherOwner = User::factory()->create(['email' => 'other@test.com']);
    $otherBusiness = app(BusinessService::class)->createForOwner($otherOwner, [
        'name' => 'Other Business',
        'state' => 'Delhi',
    ]);

    $foreignProduct = Product::factory()->forBusiness($otherBusiness)->create();

    $this->actingAs($user)
        ->get(route('inventory.ledger', $foreignProduct))
        ->assertNotFound();
});
