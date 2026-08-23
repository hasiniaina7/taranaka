<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a user without edit rights on a person is denied direct edit and can reach the proposal flow instead', function (): void {
    $owner  = User::factory()->withPersonalTeam()->create();
    $other  = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($owner)->create();

    expect($other->can('update', $person))->toBeFalse()
        ->and($other->can('propose', App\Models\Contribution::class))->toBeTrue();
});
