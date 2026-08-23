<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a contributor from another team cannot edit a person owned by a different team, even if reachable', function (): void {
    $ownerUser = User::factory()->withPersonalTeam()->create();
    $otherUser = User::factory()->withPersonalTeam()->create();

    $this->actingAs($ownerUser);
    $person = Person::factory()->withUser($ownerUser)->create();

    // Make it reachable to $otherUser's team via a shared child, without
    // granting edit rights.
    $this->actingAs($otherUser);
    Person::factory()->withUser($otherUser)->create(['father_id' => $person->id]);

    $this->get(route('people.edit-profile', $person))->assertForbidden();
});

test('a contributor from the same team can still edit their own person', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);
    $person = Person::factory()->withUser($user)->create();

    $this->get(route('people.edit-profile', $person))->assertOk();
});
