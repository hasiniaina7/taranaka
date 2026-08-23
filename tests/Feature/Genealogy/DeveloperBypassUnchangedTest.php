<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a developer sees every team person unfiltered', function (): void {
    $teamAUser = User::factory()->withPersonalTeam()->create();
    $teamBUser = User::factory()->withPersonalTeam()->create();
    $developer = User::factory()->withPersonalTeam()->create(['is_developer' => true]);

    $personA = Person::factory()->withUser($teamAUser)->create();
    $personB = Person::factory()->withUser($teamBUser)->create();

    $this->actingAs($developer);

    $ids = Person::query()->pluck('id');

    expect($ids)->toContain($personA->id)->toContain($personB->id);
});

test('a developer sees every team couple unfiltered', function (): void {
    $teamAUser = User::factory()->withPersonalTeam()->create();
    $teamBUser = User::factory()->withPersonalTeam()->create();
    $developer = User::factory()->withPersonalTeam()->create(['is_developer' => true]);

    $coupleA = Couple::factory()->create([
        'person1_id' => Person::factory()->withUser($teamAUser)->create()->id,
        'person2_id' => Person::factory()->withUser($teamAUser)->create()->id,
        'team_id'    => $teamAUser->currentTeam->id,
    ]);

    $coupleB = Couple::factory()->create([
        'person1_id' => Person::factory()->withUser($teamBUser)->create()->id,
        'person2_id' => Person::factory()->withUser($teamBUser)->create()->id,
        'team_id'    => $teamBUser->currentTeam->id,
    ]);

    $this->actingAs($developer);

    $ids = Couple::query()->pluck('id');

    expect($ids)->toContain($coupleA->id)->toContain($coupleB->id);
});
