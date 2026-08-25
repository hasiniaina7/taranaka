<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

test('the authenticated back-office person show and chart pages are unaffected by the public canvas', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create();

    $this->actingAs($user);

    $this->get(route('people.show', $person))
        ->assertOk()
        ->assertDontSee('family-tree-canvas', false)
        ->assertDontSee('familyTreeCanvas', false);

    $this->get(route('people.chart', $person))
        ->assertOk()
        ->assertDontSee('family-tree-canvas', false)
        ->assertDontSee('familyTreeCanvas', false);
});

test('the developer panel is unaffected by the public canvas', function (): void {
    $developer = User::factory()->withPersonalTeam()->create(['is_developer' => true]);

    $this->actingAs($developer);

    $this->get(route('developer.teams'))
        ->assertOk()
        ->assertDontSee('family-tree-canvas', false)
        ->assertDontSee('familyTreeCanvas', false);
});

test('the moderation queue is unaffected by the public canvas', function (): void {
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);

    $this->actingAs($moderator);

    $this->get(route('moderation.contributions'))
        ->assertOk()
        ->assertDontSee('family-tree-canvas', false)
        ->assertDontSee('familyTreeCanvas', false);
});
