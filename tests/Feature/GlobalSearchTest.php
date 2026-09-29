<?php

use App\Application\Services\BusinessContext;
use App\Domain\Authorization\SystemRoles;
use App\Models\User;
use App\Support\GlobalSearchRegistry;

test('global search palette is rendered on app pages', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('open-global-search', false)
        ->assertSee(__('Jump to page or action…'), false)
        ->assertSee(__('Jump to…'), false);
});

test('global search registry respects permissions for limited roles', function () {
    ['user' => $owner, 'business' => $business] = $this->createUserWithBusiness();
    $salesperson = User::factory()->create();
    $this->attachUserToBusiness($salesperson, $business, SystemRoles::SALESPERSON);

    $items = GlobalSearchRegistry::items($salesperson, $business);
    $labels = collect($items)->pluck('label')->all();

    expect($labels)->toContain(__('Invoices'))
        ->and($labels)->toContain(__('Customers'))
        ->and($labels)->not->toContain(__('Roles & Permissions'))
        ->and($labels)->not->toContain(__('Chart of Accounts'));
});

test('global search registry includes settings for business owners', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $items = GlobalSearchRegistry::items($user, $business);
    $labels = collect($items)->pluck('label')->all();

    expect($labels)->toContain(__('Dashboard'))
        ->and($labels)->toContain(__('Company Settings'))
        ->and($labels)->toContain(__('Team Members'))
        ->and($labels)->toContain(__('New Invoice'));
});

test('global search registry returns empty list for guests', function () {
    expect(GlobalSearchRegistry::items(null, null))->toBe([]);
});
