<?php

use App\Application\Services\BusinessContext;
use App\Support\RouteTitles;

test('pages render product name and favicon in document head', function () {
    ['user' => $user, 'business' => $business] = $this->createUserWithBusiness();

    $this->actingAs($user)
        ->withSession([BusinessContext::BUSINESS_SESSION_KEY => $business->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('<title>'.e(page_title(__('Dashboard'))).'</title>', false)
        ->assertSee('rel="icon"', false)
        ->assertSee('/favicon.svg', false)
        ->assertSee('name="theme-color"', false);
});

test('login page uses product branding in head and mark', function () {
    $productName = product_name();

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<title>'.e(page_title(__('Log in'))).'</title>', false)
        ->assertSee($productName, false)
        ->assertSee('/favicon.svg', false);
});

test('route titles resolve for settings pages', function () {
    expect(RouteTitles::forRoute(app('router')->getRoutes()->getByName('settings.invoice-templates')))
        ->toBe(__('Invoice Templates'));

    expect(RouteTitles::forRoute(app('router')->getRoutes()->getByName('sales.invoices.index')))
        ->toBe(__('Invoices'));
});

test('page title helper formats document titles', function () {
    expect(page_title())->toBe(product_name());
    expect(page_title('Dashboard'))->toBe('Dashboard — '.product_name());
});
