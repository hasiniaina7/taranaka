<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a living, non-opted-in person is protected on the public profile surface', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'yod'                 => null,
        'dod'                 => null,
        'dob'                 => '1990-06-15',
        'street'              => 'Secret Street',
        'phone'               => '0102030405',
        'is_publicly_visible' => false,
    ]);

    $response = $this->get("/p/{$person->id}");

    $response->assertOk();
    $response->assertDontSee('Secret Street');
    $response->assertDontSee('0102030405');
    $response->assertDontSee('1990-06-15');
});

test('opting a living person in reveals the profile without a separate publish step', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'yod'                 => null,
        'dod'                 => null,
        'street'              => 'Secret Street',
        'is_publicly_visible' => true,
    ]);

    $response = $this->get("/p/{$person->id}");

    $response->assertOk();
    $response->assertSee($person->firstname);
    $response->assertDontSee('Secret Street');
});
