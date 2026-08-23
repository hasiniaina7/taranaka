<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Models\Person;
use App\Models\User;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('the descendants CTE query returns a cross-team child with no team_id filtering', function (): void {
    $teamAUser = User::factory()->withPersonalTeam()->create();
    $teamBUser = User::factory()->withPersonalTeam()->create();

    $this->actingAs($teamAUser);

    $parent = Person::factory()->withUser($teamAUser)->create();

    $this->actingAs($teamBUser);

    $child = Person::factory()->withUser($teamBUser)->create(['father_id' => $parent->id]);

    $query = app(DescendantsQueryInterface::class);

    $descendants = $query->getDescendants($parent->id, 10);

    expect($descendants->pluck('id'))->toContain($child->id);
});
