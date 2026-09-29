<?php

use App\Application\Services\AccountingService;
use App\Application\Services\BusinessContext;
use App\Application\Services\BusinessService;
use App\Application\Services\ChartOfAccountsService;
use App\Domain\Accounting\DTOs\JournalLineData;
use App\Domain\Accounting\Enums\AccountStatus;
use App\Domain\Accounting\Enums\AccountType;
use App\Domain\Accounting\Exceptions\AccountHasTransactionsException;
use App\Domain\Authorization\SystemRoles;
use App\Livewire\Accounting\AccountForm;
use App\Livewire\Accounting\AccountShow;
use App\Livewire\Accounting\AccountsIndex;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

function createLedgerAccount(array $attributes = []): Account
{
    return Account::factory()->create(array_merge([
        'code' => '1600',
        'name' => 'Sundry Debtors',
        'type' => AccountType::Asset,
        'group' => 'Assets',
    ], $attributes));
}

test('chart of accounts page offers an add account action and the create form loads', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    $this->actingAs($user)
        ->get(route('accounting.accounts.index'))
        ->assertOk()
        ->assertSee('Add Account')
        ->assertSee(route('accounting.accounts.create'), false);

    $this->actingAs($user)
        ->get(route('accounting.accounts.create'))
        ->assertOk()
        ->assertSee('Account code')
        ->assertSee('Account name')
        ->assertSee('Account type')
        ->assertSee('Account group');
});

test('an account can be created against the real database', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $assets = $this->accountByCode($business, '1000');

    Livewire::actingAs($user)
        ->test(AccountForm::class)
        ->set('code', '1600')
        ->set('name', 'Sundry Debtors')
        ->set('type', AccountType::Asset->value)
        ->set('group', 'Assets')
        ->set('parentId', $assets->id)
        ->set('openingBalance', '2500.50')
        ->set('openingBalanceSide', 'debit')
        ->set('status', AccountStatus::Active->value)
        ->set('description', 'Loans given to staff')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('accounting.accounts.index'));

    $account = Account::query()->where('code', '1600')->firstOrFail();

    expect($account->name)->toBe('Sundry Debtors')
        ->and($account->type)->toBe(AccountType::Asset)
        ->and($account->group)->toBe('Assets')
        ->and($account->parent_id)->toBe($assets->id)
        ->and($account->opening_balance)->toBe(Money::rupeesToPaise(2500.50))
        ->and($account->opening_balance_side->value)->toBe('debit')
        ->and($account->status)->toBe(AccountStatus::Active)
        ->and($account->description)->toBe('Loans given to staff')
        ->and($account->is_system)->toBeFalse();
});

test('an account group is derived from the selected parent group header', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $expenses = $this->accountByCode($business, '5000');

    Livewire::actingAs($user)
        ->test(AccountForm::class)
        ->set('code', '5600')
        ->set('name', 'Repairs and Maintenance')
        ->set('parentId', $expenses->id)
        ->set('status', AccountStatus::Active->value)
        ->call('save')
        ->assertHasNoErrors();

    $account = Account::query()->where('code', '5600')->firstOrFail();

    expect($account->group)->toBe('Expenses')
        ->and($account->type)->toBe(AccountType::Expense)
        ->and($account->parent_id)->toBe($expenses->id);
});

test('a duplicate account code is rejected with a validation error', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $assets = $this->accountByCode($business, '1000');

    Livewire::actingAs($user)
        ->test(AccountForm::class)
        ->set('code', '1100')
        ->set('name', 'Duplicate Cash')
        ->set('type', AccountType::Asset->value)
        ->set('group', 'Assets')
        ->set('parentId', $assets->id)
        ->set('status', AccountStatus::Active->value)
        ->call('save')
        ->assertHasErrors(['code']);

    expect(Account::query()->where('code', '1100')->count())->toBe(1);
});

test('creating an account without a name, type or status is rejected', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    Livewire::actingAs($user)
        ->test(AccountForm::class)
        ->set('code', '')
        ->set('name', '')
        ->set('type', '')
        ->set('status', '')
        ->call('save')
        ->assertHasErrors(['code', 'name', 'type', 'status']);
});

test('an account code belonging to another business may be reused', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $assets = $this->accountByCode($business, '1000');

    $otherOwner = User::factory()->create(['email' => 'other-owner@test.com']);
    $otherBusiness = app(BusinessService::class)->createForOwner($otherOwner, [
        'name' => 'Other Business',
        'state' => 'Delhi',
    ]);
    createLedgerAccount(['business_id' => $otherBusiness->id, 'code' => '1600', 'name' => 'Foreign Debtors']);

    Livewire::actingAs($user)
        ->test(AccountForm::class)
        ->set('code', '1600')
        ->set('name', 'Sundry Debtors')
        ->set('type', AccountType::Asset->value)
        ->set('group', 'Assets')
        ->set('parentId', $assets->id)
        ->set('status', AccountStatus::Active->value)
        ->call('save')
        ->assertHasNoErrors();

    expect(Account::query()->withoutGlobalScopes()->where('business_id', $business->id)->where('code', '1600')->exists())->toBeTrue();
});

test('an account can be edited and the change is persisted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $account = createLedgerAccount(['business_id' => $business->id, 'parent_id' => $this->accountByCode($business, '1000')->id]);

    Livewire::actingAs($user)
        ->test(AccountForm::class, ['account' => $account])
        ->assertSet('name', 'Sundry Debtors')
        ->set('name', 'Trade Receivables')
        ->set('description', 'Renamed for clarity')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('accounting.accounts.index'));

    $account->refresh();

    expect($account->name)->toBe('Trade Receivables')
        ->and($account->description)->toBe('Renamed for clarity');
});

test('the code and type of an account with ledger history cannot be changed', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $account = createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
        'type' => AccountType::Asset,
        'group' => 'Assets',
    ]);

    app(AccountingService::class)->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: now()->toDateString(),
        description: 'Sundry debtor receipt',
        lines: [
            new JournalLineData(accountId: $this->accountByCode($business, '1100')->id, debitAmount: Money::rupeesToPaise(500)),
            new JournalLineData(accountId: $account->id, creditAmount: Money::rupeesToPaise(500)),
        ],
    );

    $service = app(ChartOfAccountsService::class);

    expect($service->lockedAttributes($account))->toBe([
        'code' => true,
        'type' => true,
        'opening_balance' => true,
    ]);

    expect(fn () => $service->updateAccount($account, [
        'name' => $account->name,
        'type' => AccountType::Expense,
    ]))->toThrow(AccountHasTransactionsException::class);

    expect($account->refresh()->type)->toBe(AccountType::Asset);
});

test('an account detail page shows its configuration, balance and timestamps', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $account = createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
        'opening_balance' => Money::rupeesToPaise(1000),
        'opening_balance_side' => 'debit',
        'description' => 'Detail page description',
    ]);

    $this->actingAs($user)
        ->get(route('accounting.accounts.show', $account))
        ->assertOk()
        ->assertSee('1600')
        ->assertSee('Sundry Debtors')
        ->assertSee('Asset')
        ->assertSee('Assets')
        ->assertSee('Detail page description')
        ->assertSee('Current balance')
        ->assertSee('Created')
        ->assertSee('Last updated');

    Livewire::actingAs($user)
        ->test(AccountShow::class, ['account' => $account])
        ->assertOk();
});

test('an unused account is deleted and disappears from the list', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $account = createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
    ]);

    $this->actingAs($user)
        ->get(route('accounting.accounts.index'))
        ->assertOk()
        ->assertSee('Sundry Debtors')
        ->assertSee('Delete', false);

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->call('deleteAccount', $account->id)
        ->assertHasNoErrors();

    $this->assertSoftDeleted('accounts', ['id' => $account->id]);
    expect(Account::query()->whereKey($account->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('accounting.accounts.index'))
        ->assertOk()
        ->assertDontSee('Sundry Debtors');
});

test('the delete route soft deletes an unused account', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $account = createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
    ]);

    $this->actingAs($user)
        ->delete(route('accounting.accounts.destroy', $account))
        ->assertRedirect(route('accounting.accounts.index'))
        ->assertSessionHas('status', 'Account 1600 deleted successfully.');

    $this->assertSoftDeleted('accounts', ['id' => $account->id]);
});

test('an account used in journal entries cannot be deleted and is offered deactivation instead', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $financialYear = FinancialYear::query()->withoutGlobalScopes()->where('business_id', $business->id)->firstOrFail();
    $account = createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
    ]);

    app(AccountingService::class)->postJournal(
        business: $business,
        financialYear: $financialYear,
        user: $user,
        transactionDate: now()->toDateString(),
        description: 'Sundry debtor receipt',
        lines: [
            new JournalLineData(accountId: $this->accountByCode($business, '1100')->id, debitAmount: Money::rupeesToPaise(500)),
            new JournalLineData(accountId: $account->id, creditAmount: Money::rupeesToPaise(500)),
        ],
    );

    $this->actingAs($user)
        ->delete(route('accounting.accounts.destroy', $account))
        ->assertRedirect(route('accounting.accounts.index'))
        ->assertSessionHas('error', 'The account "1600 Sundry Debtors" is already used in accounting records and cannot be deleted. Deactivate it instead to stop it being used in new transactions.');

    $this->assertNotSoftDeleted('accounts', ['id' => $account->id]);
    expect($account->refresh()->journalLines()->count())->toBe(1);

    Livewire::actingAs($user)
        ->test(AccountShow::class, ['account' => $account])
        ->assertSee('cannot be deleted')
        ->assertSee('Deactivate')
        ->assertSee('Journal Lines', false);
});

test('a group header with sub accounts cannot be deleted', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $assets = $this->accountByCode($business, '1000');

    $this->actingAs($user)
        ->delete(route('accounting.accounts.destroy', $assets))
        ->assertRedirect(route('accounting.accounts.index'))
        ->assertSessionHas('error', 'The account "1000 Assets" has sub accounts and cannot be deleted. Move or delete its sub accounts first.');

    $this->assertNotSoftDeleted('accounts', ['id' => $assets->id]);
});

test('an account can be deactivated and reactivated', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $account = createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
    ]);

    expect($account->status)->toBe(AccountStatus::Active);

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->call('toggleStatus', $account->id);

    expect($account->refresh()->status)->toBe(AccountStatus::Inactive);
    expect($account->isPostable())->toBeFalse();

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->call('toggleStatus', $account->id);

    expect($account->refresh()->status)->toBe(AccountStatus::Active);
    expect($account->isPostable())->toBeTrue();
});

test('a deactivated account is no longer offered for new journal entries', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $account = createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
    ]);

    $this->actingAs($user)
        ->get(route('accounting.journals.create'))
        ->assertOk()
        ->assertSee('Sundry Debtors');

    app(ChartOfAccountsService::class)->setStatus($account, AccountStatus::Inactive);

    $this->actingAs($user)
        ->get(route('accounting.journals.create'))
        ->assertOk()
        ->assertDontSee('Sundry Debtors');
});

test('the account list can be searched by code, name, type and group', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
        'code' => '1600',
        'name' => 'Sundry Debtors',
    ]);

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->set('search', 'Sundry')
        ->assertSee('Sundry Debtors')
        ->assertDontSee('Cost of Goods Sold');

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->set('search', '1600')
        ->assertSee('Sundry Debtors')
        ->assertDontSee('Cost of Goods Sold');

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->set('search', 'Expenses')
        ->assertSee('Cost of Goods Sold')
        ->assertDontSee('Sundry Debtors');
});

test('the account list can be filtered by type, group, status and account kind', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
        'code' => '1600',
        'name' => 'Sundry Debtors',
    ]);

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->set('type', AccountType::Expense->value)
        ->assertSee('Rent')
        ->assertDontSee('Sundry Debtors');

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->set('group', 'Assets')
        ->assertSee('Sundry Debtors')
        ->assertDontSee('Rent');

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->set('status', AccountStatus::Inactive->value)
        ->assertDontSee('Sundry Debtors')
        ->assertDontSee('Rent');

    Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->set('view', 'groups')
        ->assertSee('Assets')
        ->assertDontSee('Sundry Debtors');
});

test('the accounts excel export still downloads and honours the list filters', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
        'code' => '1600',
        'name' => 'Sundry Debtors',
    ]);

    $response = $this->actingAs($user)
        ->get(route('exports.list-excel', ['module' => 'accounts']));

    $response->assertOk();
    $response->assertDownload();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');

    $this->actingAs($user)
        ->get(route('exports.list-excel', ['module' => 'accounts', 'type' => AccountType::Expense->value]))
        ->assertOk()
        ->assertDownload();

    $this->actingAs($user)
        ->get(route('exports.list-excel', ['module' => 'accounts', 'search' => 'Sundry']))
        ->assertOk()
        ->assertDownload();

    $this->actingAs($user)
        ->get(route('exports.list-excel', ['module' => 'accounts', 'group' => 'Assets']))
        ->assertOk()
        ->assertDownload();

    $this->actingAs($user)
        ->get(route('exports.list-excel', ['module' => 'accounts', 'view' => 'groups']))
        ->assertOk()
        ->assertDownload();
});

test('the account list renders separate desktop table and mobile action menu', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
        'code' => '1600',
        'name' => 'Sundry Debtors',
    ]);

    $this->actingAs($user)
        ->get(route('accounting.accounts.index'))
        ->assertOk()
        ->assertSee('md:hidden', false)
        ->assertSee('hidden overflow-x-auto md:block', false)
        ->assertSee('View details')
        ->assertSee('Edit account')
        ->assertSee('Delete account')
        ->assertSee('Delete this account? This cannot be undone.', false);
});

test('every account is rendered as a desktop row and a mobile item across all pages', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $firstPage = Livewire::actingAs($user)->test(AccountsIndex::class);

    $firstPage->assertSee('Showing 20 accounts')
        ->assertDontSee('No accounts found.');

    expect(substr_count($firstPage->html(), '<tr wire:key="account-'))->toBe(AccountsIndex::PER_PAGE)
        ->and(substr_count($firstPage->html(), '<li wire:key="account-mobile-'))->toBe(AccountsIndex::PER_PAGE);

    $secondPage = Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->call('gotoPage', 2)
        ->assertSee('Showing 20 accounts')
        ->assertDontSee('No accounts found.');

    expect(substr_count($secondPage->html(), '<tr wire:key="account-'))
        ->toBe(Account::query()->count() - AccountsIndex::PER_PAGE);

    $renderedCodes = collect([
        $firstPage->html(),
        $secondPage->html(),
    ])->implode("\n");

    foreach (Account::query()->get() as $account) {
        expect($renderedCodes)->toContain('>'.$account->code.'<');
    }
});

test('account management requires the chart of accounts permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);
    ['business' => $otherBusiness] = $this->createUserWithBusiness(['email' => 'other@test.com']);

    $account = createLedgerAccount([
        'business_id' => $otherBusiness->id,
        'parent_id' => $this->accountByCode($otherBusiness, '1000')->id,
    ]);

    $cashier = User::factory()->create(['email' => 'cashier@test.com']);
    $this->attachUserToBusiness($cashier, $business, SystemRoles::CASHIER);
    app(BusinessContext::class)->setBusiness($business, $cashier);

    $this->actingAs($cashier)
        ->get(route('accounting.accounts.index'))->assertForbidden();
    $this->actingAs($cashier)
        ->get(route('accounting.accounts.create'))->assertForbidden();
    $this->actingAs($cashier)
        ->get(route('accounting.accounts.show', $account))->assertNotFound();
    $this->actingAs($cashier)
        ->get(route('accounting.accounts.edit', $account))->assertNotFound();
    $this->actingAs($cashier)
        ->delete(route('accounting.accounts.destroy', $account))->assertNotFound();

    $this->assertNotSoftDeleted('accounts', ['id' => $account->id]);
    expect($owner->id)->not->toBe($cashier->id);
});

test('the account policy grants every management ability to the chart of accounts permission', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness(['email' => 'owner@test.com']);

    $account = createLedgerAccount([
        'business_id' => $business->id,
        'parent_id' => $this->accountByCode($business, '1000')->id,
    ]);

    $this->actingAs($owner);

    foreach (['viewAny', 'create'] as $ability) {
        expect(Gate::forUser($owner)->allows($ability, Account::class))->toBeTrue();
    }

    foreach (['view', 'update', 'delete'] as $ability) {
        expect(Gate::forUser($owner)->allows($ability, $account))->toBeTrue();
    }
});

test('an account belonging to another business is not found', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    $otherOwner = User::factory()->create(['email' => 'other-owner@test.com']);
    $otherBusiness = app(BusinessService::class)->createForOwner($otherOwner, [
        'name' => 'Other Business',
        'state' => 'Delhi',
    ]);
    $foreignAccount = createLedgerAccount(['business_id' => $otherBusiness->id, 'name' => 'Foreign Debtors']);

    $this->actingAs($user)
        ->get(route('accounting.accounts.show', $foreignAccount))->assertNotFound();
    $this->actingAs($user)
        ->get(route('accounting.accounts.edit', $foreignAccount))->assertNotFound();
    $this->actingAs($user)
        ->delete(route('accounting.accounts.destroy', $foreignAccount))->assertNotFound();
});

test('an unknown or malformed account id is not found', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    $this->actingAs($user)
        ->get(route('accounting.accounts.show', 'not-a-valid-uuid'))->assertNotFound();
    $this->actingAs($user)
        ->get(route('accounting.accounts.edit', 'not-a-valid-uuid'))->assertNotFound();
    $this->actingAs($user)
        ->delete(route('accounting.accounts.destroy', 'not-a-valid-uuid'))->assertNotFound();
    $this->actingAs($user)
        ->get(route('accounting.accounts.show', '0192f0c0-0000-7000-8000-000000000000'))->assertNotFound();
});

test('toggling an account that does not exist fails instead of silently succeeding', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    expect(fn () => Livewire::actingAs($user)
        ->test(AccountsIndex::class)
        ->call('toggleStatus', '0192f0c0-0000-7000-8000-000000000000'))
        ->toThrow(ModelNotFoundException::class);
});

test('the account destroy route keeps csrf protection and only accepts the delete verb', function () {
    $route = Route::getRoutes()->getByName('accounting.accounts.destroy');

    expect($route)->not->toBeNull()
        ->and(in_array('web', $route->gatherMiddleware(), true))->toBeTrue()
        ->and($route->methods())->toBe(['DELETE']);
});
