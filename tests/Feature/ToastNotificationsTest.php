<?php

use App\Application\Services\SalesInvoiceService;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Livewire\Masters\CategoriesIndex;
use App\Livewire\Sales\InvoicesIndex;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Support\Money;
use Livewire\Livewire;

test('app layout includes toast container', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('aria-live="polite"', false);
});

test('livewire save action dispatches toast event', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    Livewire::actingAs($user)
        ->test(CategoriesIndex::class)
        ->set('name', 'Snacks')
        ->call('saveCategory')
        ->assertDispatched('toast');
});

test('invoice index confirm action dispatches toast event', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $customer = Party::factory()->customer()->forBusiness($business)->create();

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
            ['description' => 'Toast test item', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(100), 'tax_rate' => 18],
        ],
    );

    Livewire::actingAs($user)
        ->test(InvoicesIndex::class)
        ->call('confirmDraft', $invoice->id)
        ->assertDispatched('toast');
});
