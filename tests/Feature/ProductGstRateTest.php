<?php

use App\Application\Services\SalesInvoiceService;
use App\Domain\Products\GstRate;
use App\Domain\Shared\Exceptions\InvalidGstRateException;
use App\Domain\Transactions\Enums\DocumentStatus;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Livewire\Masters\ProductForm;
use App\Livewire\Sales\CreateInvoice;
use App\Models\Business;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\Unit;
use App\Support\Money;
use Livewire\Livewire;

function gstProductFor(Business $business, float $rate, int $sellingPrice = 100000): Product
{
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

    return Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'name' => 'Product @ '.$rate.'%',
        'selling_price' => $sellingPrice,
        'opening_stock' => 100,
        'tax_rate' => $rate,
    ]);
}

function gstInvoiceAttributes(Business $business, Party $customer): array
{
    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

    return [
        'financialYear' => $financialYear,
        'attributes' => [
            'party_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'settlement_mode' => SettlementMode::Credit,
        ],
    ];
}

test('a product saves its selected gst rate', function (float $rate) {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Slab Product')
        ->set('unitId', $unit->id)
        ->set('sellingPrice', '1000')
        ->set('taxRate', (string) $rate)
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->where('name', 'Slab Product')->firstOrFail();

    expect((float) $product->tax_rate)->toBe($rate);
})->with([0.0, 5.0, 12.0, 18.0, 28.0]);

test('a product form offers every configured gst slab', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->assertViewHas('gstRates', [
            ['value' => '0.00', 'label' => '0%'],
            ['value' => '5.00', 'label' => '5%'],
            ['value' => '12.00', 'label' => '12%'],
            ['value' => '18.00', 'label' => '18%'],
            ['value' => '28.00', 'label' => '28%'],
        ]);
});

test('editing a product shows its saved gst rate and persists a change', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $product = gstProductFor($business, 5.0);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product])
        ->assertSet('taxRate', '5.00')
        ->set('taxRate', '28.00')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $product->fresh()->tax_rate)->toBe(28.0);
});

test('a product rate outside the common slabs is still offered on the form', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $product = gstProductFor($business, 2.5);

    Livewire::actingAs($user)
        ->test(ProductForm::class, ['product' => $product])
        ->assertSet('taxRate', '2.50')
        ->assertViewHas('gstRates', fn (array $rates): bool => in_array(
            ['value' => '2.50', 'label' => '2.5%'],
            $rates,
            true,
        ));
});

test('selecting a product loads its saved gst rate onto the invoice line', function (float $rate) {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = gstProductFor($business, $rate);

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('partyId', $customer->id)
        ->set('lines.0.product_id', $product->id)
        ->assertSet('lines.0.tax_rate', (string) $product->tax_rate)
        ->assertSet('lines.0.description', $product->name);
})->with([0.0, 5.0, 12.0, 18.0, 28.0]);

test('an invoice line priced at a thousand rupees adds eighteen percent gst', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = gstProductFor($business, 18.0, Money::rupeesToPaise(1000));
    ['financialYear' => $financialYear, 'attributes' => $attributes] = gstInvoiceAttributes($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $product->id, 'description' => $product->name, 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
        ],
    );

    $line = $invoice->lines->first();

    expect($line->tax_rate)->toEqual('18.00')
        ->and($line->tax_amount)->toBe(Money::rupeesToPaise(180))
        ->and($line->line_total)->toBe(Money::rupeesToPaise(1180))
        ->and($invoice->subtotal_amount)->toBe(Money::rupeesToPaise(1000))
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(180))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(1180));
});

test('an invoice line priced at a thousand rupees adds five percent gst', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = gstProductFor($business, 5.0, Money::rupeesToPaise(1000));
    ['financialYear' => $financialYear, 'attributes' => $attributes] = gstInvoiceAttributes($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $product->id, 'description' => $product->name, 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 5],
        ],
    );

    expect($invoice->lines->first()->tax_amount)->toBe(Money::rupeesToPaise(50))
        ->and($invoice->lines->first()->line_total)->toBe(Money::rupeesToPaise(1050))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(1050));
});

test('each line of a multi product invoice uses its own gst rate', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $five = gstProductFor($business, 5.0, Money::rupeesToPaise(1000));
    $eighteen = gstProductFor($business, 18.0, Money::rupeesToPaise(1000));
    $nil = gstProductFor($business, 0.0, Money::rupeesToPaise(1000));

    ['financialYear' => $financialYear, 'attributes' => $attributes] = gstInvoiceAttributes($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $five->id, 'description' => 'Five', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 5],
            ['product_id' => $eighteen->id, 'description' => 'Eighteen', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
            ['product_id' => $nil->id, 'description' => 'Nil', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    );

    $lines = $invoice->lines->keyBy('description');

    expect((float) $lines['Five']->tax_rate)->toBe(5.0)
        ->and($lines['Five']->tax_amount)->toBe(Money::rupeesToPaise(50))
        ->and($lines['Five']->line_total)->toBe(Money::rupeesToPaise(1050))
        ->and((float) $lines['Eighteen']->tax_rate)->toBe(18.0)
        ->and($lines['Eighteen']->tax_amount)->toBe(Money::rupeesToPaise(180))
        ->and($lines['Eighteen']->line_total)->toBe(Money::rupeesToPaise(1180))
        ->and((float) $lines['Nil']->tax_rate)->toBe(0.0)
        ->and($lines['Nil']->tax_amount)->toBe(0)
        ->and($lines['Nil']->line_total)->toBe(Money::rupeesToPaise(1000))
        ->and($invoice->subtotal_amount)->toBe(Money::rupeesToPaise(3000))
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(230))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(3230));
});

test('the invoice form previews the same totals it will persist', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    $eighteen = gstProductFor($business, 18.0, Money::rupeesToPaise(1000));
    $five = gstProductFor($business, 5.0, Money::rupeesToPaise(1000));

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('partyId', $customer->id)
        ->set('lines.0.product_id', $eighteen->id)
        ->set('lines.0.quantity', '1')
        ->set('lines.0.unit_price', '1000')
        ->call('addLine')
        ->set('lines.1.product_id', $five->id)
        ->set('lines.1.quantity', '2')
        ->set('lines.1.unit_price', '1000')
        ->assertViewHas('totals', [
            'subtotal' => Money::rupeesToPaise(3000),
            'tax' => Money::rupeesToPaise(280),
            'total' => Money::rupeesToPaise(3280),
        ]);
});

test('an invoice saved as a draft keeps its per line gst without posting a journal', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = gstProductFor($business, 12.0, Money::rupeesToPaise(500));
    ['financialYear' => $financialYear, 'attributes' => $attributes] = gstInvoiceAttributes($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $product->id, 'description' => $product->name, 'quantity' => 2, 'unit_price' => Money::rupeesToPaise(500), 'tax_rate' => 12],
        ],
    );

    expect($invoice->status)->toBe(DocumentStatus::Draft)
        ->and($invoice->journal_id)->toBeNull()
        ->and($invoice->subtotal_amount)->toBe(Money::rupeesToPaise(1000))
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(120))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(1120));
});

test('confirming a gst invoice credits the gst output account and balances the journal', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = gstProductFor($business, 18.0, Money::rupeesToPaise(1000));
    ['financialYear' => $financialYear, 'attributes' => $attributes] = gstInvoiceAttributes($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $product->id, 'description' => $product->name, 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 18],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);

    $lines = $invoice->journal->lines->keyBy('account_id');
    $receivable = $this->accountByCode($business, '1300');
    $sales = $this->accountByCode($business, '4100');
    $gstOutput = $this->accountByCode($business, '2200');

    expect($invoice->status)->toBe(DocumentStatus::Confirmed)
        ->and($invoice->journal->isBalanced())->toBeTrue()
        ->and($invoice->journal->totalDebits())->toBe($invoice->journal->totalCredits())
        ->and($lines[$receivable->id]->debit_amount)->toBe(Money::rupeesToPaise(1180))
        ->and($lines[$sales->id]->credit_amount)->toBe(Money::rupeesToPaise(1000))
        ->and($lines[$gstOutput->id]->credit_amount)->toBe(Money::rupeesToPaise(180))
        ->and($invoice->journal->lines)->toHaveCount(3);
});

test('confirming a zero gst invoice posts no tax journal line', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = gstProductFor($business, 0.0, Money::rupeesToPaise(1000));
    ['financialYear' => $financialYear, 'attributes' => $attributes] = gstInvoiceAttributes($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $product->id, 'description' => $product->name, 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    );

    $invoice = app(SalesInvoiceService::class)->confirm($invoice, $user);

    $gstOutput = $this->accountByCode($business, '2200');
    $lines = $invoice->journal->lines;

    expect($invoice->tax_amount)->toBe(0)
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(1000))
        ->and($lines)->toHaveCount(2)
        ->and($lines->pluck('account_id')->all())->not->toContain($gstOutput->id)
        ->and($lines->every(fn ($line): bool => ($line->debit_amount > 0) !== ($line->credit_amount > 0)))->toBeTrue()
        ->and($invoice->journal->isBalanced())->toBeTrue();
});

test('a product without a stored gst rate falls back to nil', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

    $product = Product::factory()->forBusiness($business)->create([
        'unit_id' => $unit->id,
        'selling_price' => Money::rupeesToPaise(750),
        'tax_rate' => 0,
    ]);

    ['financialYear' => $financialYear, 'attributes' => $attributes] = gstInvoiceAttributes($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $product->id, 'description' => $product->name, 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(750)],
        ],
    );

    expect((float) $invoice->lines->first()->tax_rate)->toBe(0.0)
        ->and($invoice->tax_amount)->toBe(0)
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(750));
});

test('an invoice line takes its gst rate from the product when the request omits one', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = gstProductFor($business, 28.0, Money::rupeesToPaise(1000));
    ['financialYear' => $financialYear, 'attributes' => $attributes] = gstInvoiceAttributes($business, $customer);

    $invoice = app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $product->id, 'description' => $product->name, 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000)],
        ],
    );

    expect((float) $invoice->lines->first()->tax_rate)->toBe(28.0)
        ->and($invoice->tax_amount)->toBe(Money::rupeesToPaise(280))
        ->and($invoice->total_amount)->toBe(Money::rupeesToPaise(1280));
});

test('a tampered gst rate is rejected on the backend', function (float $rate) {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();
    $product = gstProductFor($business, 18.0, Money::rupeesToPaise(1000));
    ['financialYear' => $financialYear, 'attributes' => $attributes] = gstInvoiceAttributes($business, $customer);

    expect(fn () => app(SalesInvoiceService::class)->createDraft(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        attributes: $attributes,
        lines: [
            ['product_id' => $product->id, 'description' => $product->name, 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => $rate],
        ],
    ))->toThrow(InvalidGstRateException::class);

    expect(SalesInvoice::query()->count())->toBe(0);
})->with([-5.0, 101.0, 18.125]);

test('the invoice form rejects an invalid gst rate before it reaches the service', function (mixed $rate) {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

    Livewire::actingAs($user)
        ->test(CreateInvoice::class)
        ->set('partyId', $customer->id)
        ->set('lines.0.description', 'Manual line')
        ->set('lines.0.quantity', '1')
        ->set('lines.0.unit_price', '1000')
        ->set('lines.0.tax_rate', $rate)
        ->call('saveDraft')
        ->assertHasErrors(['lines.0.tax_rate']);

    expect(SalesInvoice::query()->count())->toBe(0);
})->with(['-5', '101', 'eighteen', '18.125']);

test('the product form rejects an invalid gst rate', function (mixed $rate) {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();
    $unit = Unit::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

    Livewire::actingAs($user)
        ->test(ProductForm::class)
        ->set('name', 'Rejected Product')
        ->set('unitId', $unit->id)
        ->set('sellingPrice', '1000')
        ->set('taxRate', $rate)
        ->call('save')
        ->assertHasErrors(['taxRate']);

    expect(Product::query()->where('name', 'Rejected Product')->exists())->toBeFalse();
})->with(['-5', '101', 'eighteen', '18.125']);

test('the gst domain rejects rates that cannot be stored', function () {
    expect(GstRate::slabs())->toBe([0.0, 5.0, 12.0, 18.0, 28.0])
        ->and(GstRate::isValid(18))->toBeTrue()
        ->and(GstRate::isValid('12.50'))->toBeTrue()
        ->and(GstRate::isValid(null))->toBeTrue()
        ->and(GstRate::isValid(''))->toBeTrue()
        ->and(GstRate::isValid(-1))->toBeFalse()
        ->and(GstRate::isValid(101))->toBeFalse()
        ->and(GstRate::isValid('eighteen'))->toBeFalse()
        ->and(GstRate::isValid(18.125))->toBeFalse()
        ->and(GstRate::normalize(null))->toBe(0.0)
        ->and(GstRate::normalize('18'))->toBe(18.0);
});
