<?php

use App\Application\Services\BusinessContext;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Livewire\Sales\CreateInvoice;
use App\Livewire\Sales\EditInvoice;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Unit;
use App\Support\Money;
use Livewire\Livewire;

function unitWithSymbol(Business $business, string $symbol): Unit
{
    return Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)
        ->where('symbol', $symbol)
        ->firstOrFail();
}

function productSoldBy(Business $business, string $name, string $unitSymbol, int $sellingPricePaise = 65000): Product
{
    return Product::factory()->forBusiness($business)->create([
        'unit_id' => unitWithSymbol($business, $unitSymbol)->id,
        'name' => $name,
        'selling_price' => $sellingPricePaise,
        'opening_stock' => 100,
        'tax_rate' => 5.0,
    ]);
}

function unitInvoiceContext(Business $business, Party $customer): array
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

test('selecting a product loads its own unit onto the invoice line', function (string $symbol) {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = productSoldBy($business, 'Basmati Rice', $symbol);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('partyId', $customer->id)
        ->set('lines.0.product_id', $product->id)
        ->assertSet('lines.0.unit_id', $product->unit_id)
        ->assertViewHas('units', fn ($units): bool => $units->contains('symbol', $symbol));
})->with(['Kg', 'Pcs', 'Ltr', 'Mtr']);

test('changing the product on a line replaces its unit', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $rice = productSoldBy($business, 'Basmati Rice', 'Kg');
    $cloth = productSoldBy($business, 'Cotton Cloth', 'Mtr');

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('partyId', $customer->id)
        ->set('lines.0.product_id', $rice->id)
        ->assertSet('lines.0.unit_id', $rice->unit_id)
        ->set('lines.0.product_id', $cloth->id)
        ->assertSet('lines.0.unit_id', $cloth->unit_id)
        ->assertSet('lines.0.description', 'Cotton Cloth');
});

test('switching a line back to a manual line clears the unit', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $product = productSoldBy($business, 'Basmati Rice', 'Kg');

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('lines.0.product_id', $product->id)
        ->assertSet('lines.0.unit_id', $product->unit_id)
        ->set('lines.0.product_id', '')
        ->assertSet('lines.0.unit_id', '');
});

test('each added line shows the unit of its own product', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $rice = productSoldBy($business, 'Basmati Rice', 'Kg');
    $milk = productSoldBy($business, 'Milk', 'Ltr');
    $pen = productSoldBy($business, 'Pen', 'Pcs');
    $cloth = productSoldBy($business, 'Cotton Cloth', 'Mtr');

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('partyId', $customer->id)
        ->set('lines.0.product_id', $rice->id)
        ->call('addLine')
        ->set('lines.1.product_id', $milk->id)
        ->call('addLine')
        ->set('lines.2.product_id', $pen->id)
        ->call('addLine')
        ->set('lines.3.product_id', $cloth->id)
        ->assertSet('lines.0.unit_id', $rice->unit_id)
        ->assertSet('lines.1.unit_id', $milk->unit_id)
        ->assertSet('lines.2.unit_id', $pen->unit_id)
        ->assertSet('lines.3.unit_id', $cloth->unit_id);
});

test('a draft invoice stores the unit of each product on its own line', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $rice = productSoldBy($business, 'Basmati Rice', 'Kg');
    $milk = productSoldBy($business, 'Milk', 'Ltr');
    $pen = productSoldBy($business, 'Pen', 'Pcs');
    $cloth = productSoldBy($business, 'Cotton Cloth', 'Mtr');
    ['financialYear' => $financialYear, 'attributes' => $attributes] = unitInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $rice->id, 'description' => 'Basmati Rice', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(650), 'tax_rate' => 5],
            ['product_id' => $milk->id, 'description' => 'Milk', 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(60), 'tax_rate' => 5],
            ['product_id' => $pen->id, 'description' => 'Pen', 'quantity' => 4, 'unit_price' => Money::rupeesToPaise(10), 'tax_rate' => 12],
            ['product_id' => $cloth->id, 'description' => 'Cotton Cloth', 'quantity' => 3, 'unit_price' => Money::rupeesToPaise(250), 'tax_rate' => 5],
        ],
    );

    $lines = $invoice->lines->keyBy('description');

    expect($invoice->status)->toBe(DocumentStatus::Draft)
        ->and($lines['Basmati Rice']->unit?->symbol)->toBe('Kg')
        ->and($lines['Milk']->unit?->symbol)->toBe('Ltr')
        ->and($lines['Pen']->unit?->symbol)->toBe('Pcs')
        ->and($lines['Cotton Cloth']->unit?->symbol)->toBe('Mtr');
});

test('confirming an invoice keeps the unit on every line and leaves the totals untouched', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $rice = productSoldBy($business, 'Basmati Rice', 'Kg', Money::rupeesToPaise(650));
    $milk = productSoldBy($business, 'Milk', 'Ltr', Money::rupeesToPaise(60));
    ['financialYear' => $financialYear, 'attributes' => $attributes] = unitInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $rice->id, 'description' => 'Basmati Rice', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(650), 'tax_rate' => 5],
            ['product_id' => $milk->id, 'description' => 'Milk', 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(60), 'tax_rate' => 5],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);

    expect($invoice->status)->toBe(DocumentStatus::Confirmed)
        ->and($invoice->lines->keyBy('description')['Basmati Rice']->unit?->symbol)->toBe('Kg')
        ->and($invoice->lines->keyBy('description')['Milk']->unit?->symbol)->toBe('Ltr')
        ->and($invoice->subtotal_amount)->toBe(Money::rupeesToPaise(770))
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(38.5))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(808.5));
});

test('a stored line unit survives a later change to the product unit', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $rice = productSoldBy($business, 'Basmati Rice', 'Kg');
    ['financialYear' => $financialYear, 'attributes' => $attributes] = unitInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $rice->id, 'description' => 'Basmati Rice', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(650), 'tax_rate' => 5],
        ],
    );

    $rice->update(['unit_id' => unitWithSymbol($business, 'Pcs')->id]);

    $line = $invoice->lines->first()->fresh('unit');

    expect($line->unit?->symbol)->toBe('Kg')
        ->and($line->product->fresh()->unit?->symbol)->toBe('Pcs');
});

test('editing a draft shows the unit stored on each line', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $rice = productSoldBy($business, 'Basmati Rice', 'Kg');
    $cloth = productSoldBy($business, 'Cotton Cloth', 'Mtr');
    ['financialYear' => $financialYear, 'attributes' => $attributes] = unitInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $rice->id, 'description' => 'Basmati Rice', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(650), 'tax_rate' => 5],
            ['product_id' => $cloth->id, 'description' => 'Cotton Cloth', 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(250), 'tax_rate' => 5],
        ],
    );

    Livewire::actingAs($user)
        ->test(EditInvoice::class, ['invoice' => $invoice])
        ->assertSet('lines.0.unit_id', $rice->unit_id)
        ->assertSet('lines.1.unit_id', $cloth->unit_id);
});

test('a manual line with no product stores no unit', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    ['financialYear' => $financialYear, 'attributes' => $attributes] = unitInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => null, 'unit_id' => null, 'description' => 'Manual work', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 0],
        ],
    );

    expect($invoice->lines->first()->unit_id)->toBeNull();
});

test('a unit belonging to another business is not stored on a line', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    ['business' => $otherBusiness] = $this->createUserWithBusiness();
    $foreignUnit = unitWithSymbol($otherBusiness, 'Kg');

    app(BusinessContext::class)->setBusiness($business, $user);

    ['financialYear' => $financialYear, 'attributes' => $attributes] = unitInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => null, 'unit_id' => $foreignUnit->id, 'description' => 'Manual work', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 0],
        ],
    );

    expect($invoice->lines->first()->unit_id)->toBeNull();
});

test('the new invoice page renders a unit column between qty and rate', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    productSoldBy($business, 'Basmati Rice', 'Kg');

    $this->actingAs($user)
        ->get(route('sales.invoices.create'))
        ->assertOk()
        ->assertSeeInOrder([__('Qty'), __('Unit'), __('Rate (₹)'), __('GST %')]);
});

test('an existing draft invoice shows its stored unit when edited', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $rice = productSoldBy($business, 'Basmati Rice', 'Kg');
    ['financialYear' => $financialYear, 'attributes' => $attributes] = unitInvoiceContext($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $rice->id, 'description' => 'Basmati Rice', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(650), 'tax_rate' => 5],
        ],
    );

    Livewire::actingAs($user)
        ->test(EditInvoice::class, ['invoice' => $invoice])
        ->assertSet('lines.0.unit_id', $rice->unit_id)
        ->assertSeeInOrder([__('Qty'), __('Unit'), __('Rate (₹)')])
        ->assertSeeHtml('<option value="'.$rice->unit_id.'">Kg</option>');
});

test('saving an invoice from the form persists the unit it displayed', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $rice = productSoldBy($business, 'Basmati Rice', 'Kg', Money::rupeesToPaise(650));

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('partyId', $customer->id)
        ->set('lines.0.product_id', $rice->id)
        ->set('lines.0.quantity', '1')
        ->set('lines.0.unit_price', '650')
        ->call('saveDraft')
        ->assertHasNoErrors();

    $invoice = SalesInvoice::query()->latest('created_at')->firstOrFail();
    $line = $invoice->lines->first();

    expect($line->unit?->symbol)->toBe('Kg')
        ->and((string) $line->quantity)->toBe('1.0000')
        ->and($line->unit_price)->toBe(Money::rupeesToPaise(650))
        ->and((float) $line->tax_rate)->toBe(5.0)
        ->and($invoice->subtotal_amount)->toBe(Money::rupeesToPaise(650))
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(32.5))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(682.5));
});
