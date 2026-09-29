<?php

it('redirects guests to login', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login'));
});

it('redirects authenticated users to dashboard', function () {
    ['user' => $user] = $this->createUserWithBusiness();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('dashboard'));
});
