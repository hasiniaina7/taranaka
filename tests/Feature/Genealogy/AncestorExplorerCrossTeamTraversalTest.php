<?php

declare(strict_types=1);

use App\Contracts\AncestorsQueryInterface;
use App\Models\Person;
use App\Models\User;

test('the ancestor explorer query crosses team boundaries without duplicates', function (): void {
    $firstTeamUser  = User::factory()->withPersonalTeam()->create();
    $secondTeamUser = User::factory()->withPersonalTeam()->create();

    test()->actingAs($firstTeamUser);
    $grandparent = Person::factory()->withUser($firstTeamUser)->create();

    test()->actingAs($secondTeamUser);
    $parent = Person::factory()->withUser($secondTeamUser)->create(['father_id' => $grandparent->id]);
    $child  = Person::factory()->withUser($secondTeamUser)->create(['father_id' => $parent->id]);

    $ancestors = app(AncestorsQueryInterface::class)->getAncestors($child->id, 3);

    expect($ancestors->pluck('id'))->toContain($parent->id, $grandparent->id);
    expect($ancestors->pluck('id')->duplicates())->toBeEmpty();
});
