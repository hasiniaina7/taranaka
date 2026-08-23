<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a user without edit rights on the person is denied togglePrivacy directly', function (): void {
    $owner  = User::factory()->withPersonalTeam()->create();
    $other  = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($owner)->create();

    expect($other->can('togglePrivacy', $person))->toBeFalse();
});

test('mounting the privacy toggle component is denied for a user without edit rights', function (): void {
    $owner  = User::factory()->withPersonalTeam()->create();
    $other  = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($owner)->create();

    $this->actingAs($other);

    Livewire\Livewire::test('people::edit.privacy', ['person' => $person])
        ->assertForbidden();
});
