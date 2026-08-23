<?php

declare(strict_types=1);

use App\Contracts\AncestorsQueryInterface;
use App\Contracts\DescendantsQueryInterface;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('descendant and ancestor traversal crosses the team boundary as one connected chain with no duplicates', function (): void {
    $teamXUser = User::factory()->withPersonalTeam()->create();
    $teamYUser = User::factory()->withPersonalTeam()->create();

    $this->actingAs($teamXUser);
    $parent = Person::factory()->withUser($teamXUser)->create();

    $this->actingAs($teamYUser);
    $child = Person::factory()->withUser($teamYUser)->create(['father_id' => $parent->id]);

    $descendants = app(DescendantsQueryInterface::class)->getDescendants($parent->id, 10);
    $ancestors   = app(AncestorsQueryInterface::class)->getAncestors($child->id, 10);

    expect($descendants->pluck('id')->duplicates())->toBeEmpty();
    expect($descendants->pluck('id'))->toContain($child->id);

    expect($ancestors->pluck('id')->duplicates())->toBeEmpty();
    expect($ancestors->pluck('id'))->toContain($parent->id);
});
