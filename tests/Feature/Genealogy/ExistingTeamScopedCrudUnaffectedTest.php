<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a contributor can create, edit and delete a person within their own team', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $person = Person::factory()->withUser($user)->create();

    $this->assertDatabaseHas('people', ['id' => $person->id]);

    $person->update(['firstname' => 'Updated']);

    $this->assertDatabaseHas('people', ['id' => $person->id, 'firstname' => 'Updated']);

    $person->delete();

    $this->assertSoftDeleted($person);
});

test('a contributor can create and delete a couple within their own team', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $this->actingAs($user);

    $person1 = Person::factory()->withUser($user)->create();
    $person2 = Person::factory()->withUser($user)->create();

    $couple = Couple::factory()->create([
        'person1_id' => $person1->id,
        'person2_id' => $person2->id,
        'team_id'    => $user->currentTeam->id,
    ]);

    $this->assertDatabaseHas('couples', ['id' => $couple->id]);

    $couple->delete();

    $this->assertDatabaseMissing('couples', ['id' => $couple->id]);
});

test('a contributor from another team cannot see an isolated team person via the default scope', function (): void {
    $ownerTeamUser = User::factory()->withPersonalTeam()->create();
    $otherTeamUser = User::factory()->withPersonalTeam()->create();

    $person = Person::factory()->withUser($ownerTeamUser)->create();

    $this->actingAs($otherTeamUser);

    expect(Person::query()->find($person->id))->toBeNull();
});

test('a contributor from another team cannot see an isolated team couple via the default scope', function (): void {
    $ownerTeamUser = User::factory()->withPersonalTeam()->create();
    $otherTeamUser = User::factory()->withPersonalTeam()->create();

    $person1 = Person::factory()->withUser($ownerTeamUser)->create();
    $person2 = Person::factory()->withUser($ownerTeamUser)->create();

    $couple = Couple::factory()->create([
        'person1_id' => $person1->id,
        'person2_id' => $person2->id,
        'team_id'    => $ownerTeamUser->currentTeam->id,
    ]);

    $this->actingAs($otherTeamUser);

    expect(Couple::query()->find($couple->id))->toBeNull();
});
