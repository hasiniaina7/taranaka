<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('the profile shows the ownership badge and CTA for a person owned by another team', function (): void {
    $ownerUser = User::factory()->withPersonalTeam()->create();
    $otherUser = User::factory()->withPersonalTeam()->create();

    $this->actingAs($ownerUser);
    $person = Person::factory()->withUser($ownerUser)->create();

    // Make it visible (but not editable) to $otherUser's team via a shared child.
    $this->actingAs($otherUser);
    Person::factory()->withUser($otherUser)->create(['father_id' => $person->id]);

    Livewire::test('people::profile', ['person' => $person])
        ->assertSee(__('person.managed_by_another_team'))
        ->assertSee(__('person.propose_edit'))
        ->assertDontSee(__('person.edit_profile'));
});

test('the profile shows the normal edit menu with no badge for a person owned by the same team', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);
    $person = Person::factory()->withUser($user)->create();

    Livewire::test('people::profile', ['person' => $person])
        ->assertDontSee(__('person.managed_by_another_team'))
        ->assertDontSee(__('person.propose_edit'))
        ->assertSee(__('person.edit_profile'));
});
