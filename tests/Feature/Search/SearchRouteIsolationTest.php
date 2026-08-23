<?php

declare(strict_types=1);

test('public global search does not collide with authenticated team search', function (): void {
    expect(route('public.search', absolute: false))->toBe('/search');
    expect(route('people.search', absolute: false))->toBe('/people/search');

    test()->get(route('public.search'))
        ->assertOk();

    test()->get(route('people.search'))
        ->assertRedirect(route('login'));
});
