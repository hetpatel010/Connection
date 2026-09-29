<?php

use App\Application\Services\FinancialYearService;
use App\Application\Services\SalesInvoiceService;
use App\Domain\Authorization\SystemRoles;
use App\Domain\FinancialYear\Enums\FinancialYearStatus;
use App\Domain\Shared\Exceptions\FinancialYearClosedException;
use App\Domain\Transactions\Enums\SettlementMode;
use App\Livewire\Settings\FinancialYearsIndex;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\User;
use App\Support\Money;
use Livewire\Livewire;

test('open next financial year creates the following indian financial year', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $current = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    app(FinancialYearService::class)->close($current, $user);

    $next = app(FinancialYearService::class)->openNext($business, $user);

    $expectedStartsOn = $current->ends_on->copy()->addDay();
    $expectedEndsOn = $expectedStartsOn->copy()->addYear()->subDay();
    $expectedLabel = sprintf('%d-%02d', $expectedStartsOn->year, ($expectedStartsOn->year + 1) % 100);

    expect($next->label)->toBe($expectedLabel)
        ->and($next->starts_on->toDateString())->toBe($expectedStartsOn->toDateString())
        ->and($next->ends_on->toDateString())->toBe($expectedEndsOn->toDateString())
        ->and($next->status)->toBe(FinancialYearStatus::Open)
        ->and($next->is_current)->toBeTrue();

    expect(FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->count())->toBe(2);
});

test('next financial year cannot be opened until the latest year is closed', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    expect(fn () => app(FinancialYearService::class)->openNext($business, $user))
        ->toThrow(FinancialYearClosedException::class);
});

test('confirmed transactions cannot be posted into a closed financial year', function () {
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
            ['description' => 'Service', 'quantity' => 1, 'unit_price' => Money::rupeesToPaise(1000), 'tax_rate' => 0],
        ],
    );

    app(FinancialYearService::class)->close($financialYear, $user);

    expect(fn () => app(SalesInvoiceService::class)->confirm($invoice, $user))
        ->toThrow(FinancialYearClosedException::class);
});

test('financial years page requires close_financial_year permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $this->actingAs($owner)
        ->get(route('settings.financial-years'))
        ->assertOk();

    $accountant = User::factory()->create(['email' => 'accountant@test.com']);
    $this->attachUserToBusiness($accountant, $business, SystemRoles::ACCOUNTANT);

    $this->actingAs($accountant)
        ->get(route('settings.financial-years'))
        ->assertOk();

    $salesperson = User::factory()->create(['email' => 'sales@test.com']);
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $this->actingAs($salesperson)
        ->get(route('settings.financial-years'))
        ->assertForbidden();
});

test('owner can close and open next financial year from settings ui', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();

    Livewire::actingAs($owner)
        ->test(FinancialYearsIndex::class)
        ->call('closeFinancialYear', $financialYear->id)
        ->assertHasNoErrors();

    expect($financialYear->fresh()->status)->toBe(FinancialYearStatus::Closed);

    Livewire::actingAs($owner)
        ->test(FinancialYearsIndex::class)
        ->call('openNextFinancialYear')
        ->assertHasNoErrors();

    $expectedLabel = sprintf(
        '%d-%02d',
        $financialYear->ends_on->copy()->addDay()->year,
        ($financialYear->ends_on->copy()->addDay()->year + 1) % 100,
    );

    $next = FinancialYear::query()
        ->withoutGlobalScopes()
        ->where('business_id', $business->id)
        ->where('label', $expectedLabel)
        ->first();

    expect($next)->not->toBeNull()
        ->and($next->is_current)->toBeTrue();
});
