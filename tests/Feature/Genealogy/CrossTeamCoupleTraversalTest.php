<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a shared child of a cross-team couple is visible to both teams', function (): void {
    $teamXUser = User::factory()->withPersonalTeam()->create();
    $teamYUser = User::factory()->withPersonalTeam()->create();

    $this->actingAs($teamXUser);
    $personA = Person::factory()->withUser($teamXUser)->create();

    $this->actingAs($teamYUser);
    $personB = Person::factory()->withUser($teamYUser)->create();

    // Act as a developer while seeding the couple: CoupleFactory::definition()
    // unconditionally picks two random *visible* people before the explicit
    // overrides below replace them, and at this point personA/personB are
    // not yet cross-visible to each other's team, so a team-scoped actor
    // would only ever see one candidate and loop forever.
    $developer = User::factory()->withPersonalTeam()->create(['is_developer' => true]);
    $this->actingAs($developer);

    $couple = Couple::factory()->create([
        'person1_id' => $personA->id,
        'person2_id' => $personB->id,
        'team_id'    => $teamXUser->currentTeam->id,
    ]);

    $child = Person::factory()->withUser($teamYUser)->create(['father_id' => $personA->id]);

    $this->actingAs($teamXUser);
    expect(Person::query()->find($child->id))->not->toBeNull();
    expect(Couple::query()->find($couple->id))->not->toBeNull();

    $this->actingAs($teamYUser);
    expect(Person::query()->find($child->id))->not->toBeNull();
    expect(Couple::query()->find($couple->id))->not->toBeNull();
});
