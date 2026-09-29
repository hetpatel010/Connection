<?php

use App\Application\Services\SalesInvoiceService;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Livewire\Masters\ProductForm;
use App\Livewire\Sales\CreateInvoice;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\Unit;
use App\Support\Money;
use Livewire\Livewire;

function priceUnit(Business $business, string $symbol): Unit
{
    return Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)
        ->where('symbol', $symbol)
        ->firstOrFail();
}

function priceProduct(Business $business, string $name, string $symbol, float $rupees, float $gst): Product
{
    return Product::factory()->forBusiness($business)->create([
        'unit_id' => priceUnit($business, $symbol)->id,
        'name' => $name,
        'selling_price' => Money::rupeesToPaise($rupees),
        'tax_rate' => $gst,
        'opening_stock' => 100,
    ]);
}

function priceInvoiceContext(Business $business, Party $customer): array
{
    return [
        'financialYear' => FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail(),
        'attributes' => [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
    ];
}

function priceCustomer(Business $business): Party
{
    return Party::factory()->customer()->forBusiness($business)->create();
}

test('selecting coke loads its saved unit rate and gst onto the line', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $coke = priceProduct($business, 'Coke', 'Pcs', 50, 18);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('lines.0.product_id', $coke->id)
        ->assertSet('lines.0.description', 'Coke')
        ->assertSet('lines.0.unit_id', $coke->unit_id)
        ->assertSet('lines.0.unit_price', '50')
        ->assertSet('lines.0.tax_rate', '18.00');
});

test('selecting basmati rice loads its own unit rate and gst', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $rice = priceProduct($business, 'Basmati Rice', 'Kg', 650, 5);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('lines.0.product_id', $rice->id)
        ->assertSet('lines.0.description', 'Basmati Rice')
        ->assertSet('lines.0.unit_id', $rice->unit_id)
        ->assertSet('lines.0.unit_price', '650')
        ->assertSet('lines.0.tax_rate', '5.00');
});

test('selecting milk loads its saved litre unit and rate', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $milk = priceProduct($business, 'Milk', 'Ltr', 60, 5);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('lines.0.product_id', $milk->id)
        ->assertSet('lines.0.unit_id', $milk->unit_id)
        ->assertSet('lines.0.unit_price', '60')
        ->assertSet('lines.0.tax_rate', '5.00');
});

test('each product loads its own rate and gst rather than a shared default', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $rice = priceProduct($business, 'Basmati Rice', 'Kg', 650, 5);
    $mouse = priceProduct($business, 'Wireless Mouse', 'Pcs', 850, 18);
    $free = priceProduct($business, 'Sample', 'Pcs', 10, 0);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('lines.0.product_id', $rice->id)
        ->set('lines.0.product_id', $mouse->id)
        ->assertSet('lines.0.unit_price', '850')
        ->assertSet('lines.0.tax_rate', '18.00')
        ->set('lines.0.product_id', $free->id)
        ->assertSet('lines.0.unit_price', '10')
        ->assertSet('lines.0.tax_rate', '0.00');
});

test('one coke at fifty rupees adds eighteen percent gst', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = priceCustomer($business);
    $coke = priceProduct($business, 'Coke', 'Pcs', 50, 18);
    ['financialYear' => $financialYear, 'attributes' => $attributes] = priceInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $coke->id, 'unit_id' => $coke->unit_id, 'description' => 'Coke', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(50), 'tax_rate' => 18],
        ],
    );

    $line = $invoice->lines->first();

    expect((float) $line->tax_rate)->toBe(18.0)
        ->and($line->unit?->symbol)->toBe('Pcs')
        ->and($line->line_total)->toBe(Money::rupeesToPaise(59))
        ->and($invoice->subtotal_amount)->toBe(Money::rupeesToPaise(50))
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(9))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(59));
});

test('two cokes double the subtotal gst and total', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = priceCustomer($business);
    $coke = priceProduct($business, 'Coke', 'Pcs', 50, 18);
    ['financialYear' => $financialYear, 'attributes' => $attributes] = priceInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $coke->id, 'unit_id' => $coke->unit_id, 'description' => 'Coke', 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(50), 'tax_rate' => 18],
        ],
    );

    expect($invoice->subtotal_amount)->toBe(Money::rupeesToPaise(100))
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(18))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(118));
});

test('coke and basmati rice on one invoice each calculate independently', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = priceCustomer($business);
    $coke = priceProduct($business, 'Coke', 'Pcs', 50, 18);
    $rice = priceProduct($business, 'Basmati Rice', 'Kg', 650, 5);
    ['financialYear' => $financialYear, 'attributes' => $attributes] = priceInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $coke->id, 'unit_id' => $coke->unit_id, 'description' => 'Coke', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(50), 'tax_rate' => 18],
            ['product_id' => $rice->id, 'unit_id' => $rice->unit_id, 'description' => 'Basmati Rice', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(650), 'tax_rate' => 5],
        ],
    );

    $lines = $invoice->lines->keyBy('description');

    expect($lines['Coke']->unit?->symbol)->toBe('Pcs')
        ->and($lines['Coke']->line_total)->toBe(Money::rupeesToPaise(59))
        ->and($lines['Basmati Rice']->unit?->symbol)->toBe('Kg')
        ->and($lines['Basmati Rice']->line_total)->toBe(Money::rupeesToPaise(682.5))
        ->and($invoice->subtotal_amount)->toBe(Money::rupeesToPaise(700))
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(41.5))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(741.5));
});

test('the form totals react to quantity changes without reloading', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $coke = priceProduct($business, 'Coke', 'Pcs', 50, 18);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('lines.0.product_id', $coke->id)
        ->set('lines.0.quantity', '1')
        ->assertViewHas('totals', [
            'subtotal' => Money::rupeesToPaise(50),
            'tax' => Money::rupeesToPaise(9),
            'total' => Money::rupeesToPaise(59),
        ])
        ->set('lines.0.quantity', '2')
        ->assertViewHas('totals', [
            'subtotal' => Money::rupeesToPaise(100),
            'tax' => Money::rupeesToPaise(18),
            'total' => Money::rupeesToPaise(118),
        ]);
});

test('the form totals react to a rate override', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $coke = priceProduct($business, 'Coke', 'Pcs', 50, 18);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('lines.0.product_id', $coke->id)
        ->set('lines.0.quantity', '1')
        ->set('lines.0.unit_price', '200')
        ->assertViewHas('totals', [
            'subtotal' => Money::rupeesToPaise(200),
            'tax' => Money::rupeesToPaise(36),
            'total' => Money::rupeesToPaise(236),
        ]);
});

test('confirming a gst invoice posts the gst output line and balances the journal', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = priceCustomer($business);
    $coke = priceProduct($business, 'Coke', 'Pcs', 50, 18);
    $rice = priceProduct($business, 'Basmati Rice', 'Kg', 650, 5);
    ['financialYear' => $financialYear, 'attributes' => $attributes] = priceInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $coke->id, 'unit_id' => $coke->unit_id, 'description' => 'Coke', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(50), 'tax_rate' => 18],
            ['product_id' => $rice->id, 'unit_id' => $rice->unit_id, 'description' => 'Basmati Rice', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(650), 'tax_rate' => 5],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);

    $gstOutput = $this->accountByCode($business, '2200');
    $gstLines = $invoice->journal->lines->where('account_id', $gstOutput->id);

    expect($gstLines)->toHaveCount(1)
        ->and($gstLines->first()->credit_amount)->toBe(Money::rupeesToPaise(41.5))
        ->and($invoice->journal->isBalanced())->toBeTrue()
        ->and($invoice->journal->totalDebits())->toBe($invoice->journal->totalCredits())
        ->and($invoice->journal->totalDebits())->toBe(Money::rupeesToPaise(741.5));
});

test('a zero gst product posts no gst journal line and still balances', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = priceCustomer($business);
    $free = priceProduct($business, 'Sample', 'Pcs', 100, 0);
    ['financialYear' => $financialYear, 'attributes' => $attributes] = priceInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $free->id, 'unit_id' => $free->unit_id, 'description' => 'Sample', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 0],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);

    $gstOutput = $this->accountByCode($business, '2200');

    expect($invoice->tax_amount)->toBe(0)
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(100))
        ->and($invoice->journal->lines)->toHaveCount(2)
        ->and($invoice->journal->lines->pluck('account_id')->all())->not->toContain($gstOutput->id)
        ->and($invoice->journal->isBalanced())->toBeTrue()
        ->and($invoice->journal->totalDebits())->toBe($invoice->journal->totalCredits());
});

test('a line falls back to the products saved rate when the request omits one', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = priceCustomer($business);
    $coke = priceProduct($business, 'Coke', 'Pcs', 50, 18);
    ['financialYear' => $financialYear, 'attributes' => $attributes] = priceInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $coke->id, 'description' => 'Coke', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(50)],
        ],
    );

    expect((float) $invoice->lines->first()->tax_rate)->toBe(18.0)
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(9))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(59));
});

test('a line takes its unit from the product even when the request sends a different one', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = priceCustomer($business);
    $coke = priceProduct($business, 'Coke', 'Pcs', 50, 18);
    $wrongUnit = priceUnit($business, 'Kg');
    ['financialYear' => $financialYear, 'attributes' => $attributes] = priceInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $coke->id, 'unit_id' => $wrongUnit->id, 'description' => 'Coke', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(50), 'tax_rate' => 18],
        ],
    );

    expect($invoice->lines->first()->unit?->symbol)->toBe('Pcs');
});

test('the product form saves the rate and gst it was given', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $unit = priceUnit($business, 'Pcs');

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Coke')
        ->set('unitId', $unit->id)
        ->set('sellingPrice', '50')
        ->set('taxRate', '18.00')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('name', 'Coke')->firstOrFail();

    expect($product->selling_price)->toBe(Money::rupeesToPaise(50))
        ->and((float) $product->tax_rate)->toBe(18.0)
        ->and($product->unit_id)->toBe($unit->id)
        ->and($product->fresh()->selling_price)->toBe(Money::rupeesToPaise(50));
});

test('a product saved through the form feeds the invoice rate and gst', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $unit = priceUnit($business, 'Pcs');

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Coke')
        ->set('unitId', $unit->id)
        ->set('sellingPrice', '50')
        ->set('taxRate', '18.00')
        ->call('save');

    $coke = Product::query()->where('name', 'Coke')->firstOrFail();

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('lines.0.product_id', $coke->id)
        ->assertSet('lines.0.unit_price', '50')
        ->assertSet('lines.0.tax_rate', '18.00');
});

test('the product form refuses to save a product with no selling price', function (string $price) {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $unit = priceUnit($business, 'Pcs');

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Priceless')
        ->set('unitId', $unit->id)
        ->set('sellingPrice', $price)
        ->set('taxRate', '18.00')
        ->call('save')
        ->assertHasErrors(['sellingPrice']);

    expect(Product::query()->where('name', 'Priceless')->exists())->toBeFalse();
})->with(['', '0', '0.00']);

test('the product form refuses to save a product with no gst rate', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $unit = priceUnit($business, 'Pcs');

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Unrated')
        ->set('unitId', $unit->id)
        ->set('sellingPrice', '50')
        ->set('taxRate', '')
        ->call('save')
        ->assertHasErrors(['taxRate']);

    expect(Product::query()->where('name', 'Unrated')->exists())->toBeFalse();
});

test('editing a product replaces a zero selling price with a real one', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $unit = priceUnit($business, 'Pcs');

    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'name' => 'Old Coke',
        'selling_price' => 0,
        'tax_rate' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product])
        ->call('save')
        ->assertHasErrors(['sellingPrice']);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product])
        ->set('sellingPrice', '50')
        ->set('taxRate', '18.00')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->selling_price)->toBe(Money::rupeesToPaise(50))
        ->and((float) $product->fresh()->tax_rate)->toBe(18.0);
});

test('the invoice form flags a product that has no selling price saved', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $unit = priceUnit($business, 'Pcs');

    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'name' => 'Unpriced Item',
        'selling_price' => 0,
        'tax_rate' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('lines.0.product_id', $product->id)
        ->assertSee(__('This product has no selling price saved — set its rate manually.'));
});
