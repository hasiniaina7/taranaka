<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a living, non-opted-in person profile withholds sensitive fields and shows the privacy banner', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'yod'         => null,
        'dod'         => null,
        'dob'         => '1990-06-15',
        'street'      => 'Secret Street',
        'number'      => '42',
        'postal_code' => '75000',
        'city'        => 'Paris',
        'phone'       => '0102030405',
    ]);

    $response = $this->get("/p/{$person->id}");

    $response->assertOk();
    $response->assertDontSee('Secret Street');
    $response->assertDontSee('0102030405');
    $response->assertDontSee('1990-06-15');
    $response->assertSee(__('person.profile_private'));
});

test('an authenticated contributor with edit rights still sees full data on the authenticated route', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'yod'    => null,
        'dod'    => null,
        'street' => 'Secret Street',
        'phone'  => '0102030405',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('people.show', $person));

    $response->assertOk();
    $response->assertSee('Secret Street');
    $response->assertSee('0102030405');
});
