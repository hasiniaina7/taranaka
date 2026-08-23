<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('is_publicly_visible defaults to false for a newly created person', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create();

    expect($person->fresh()->is_publicly_visible)->toBeFalse();
});

test('is_publicly_visible column does not disturb other people column attributes', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create(['firstname' => 'Ada', 'surname' => 'Lovelace']);

    $person->refresh();

    expect($person->firstname)->toBe('Ada')
        ->and($person->surname)->toBe('Lovelace')
        ->and($person->is_publicly_visible)->toBeFalse();
});
