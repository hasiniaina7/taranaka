<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Livewire\People\Descendants\Tree;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\Support\FakeDescendantsQuery;

test('the full bounded tree is computed in a single query and every node id is reachable for client-side navigation', function (): void {
    // Since spec 012, the canvas needs the whole bounded tree up front (so
    // pan/zoom/expand-collapse happen client-side without further requests);
    // "every visible node links to its profile" is now enforced by
    // family-tree.js building each card's profile URL from `personId`
    // (resources/js/family-tree.js), not by a server-rendered <a href>.
    $user       = User::factory()->withPersonalTeam()->create();
    $root       = Person::factory()->withUser($user)->create(['firstname' => 'Root', 'yod' => 1990]);
    $child      = Person::factory()->withUser($user)->create(['firstname' => 'Branch', 'father_id' => $root->id, 'yod' => 2010]);
    $grandchild = Person::factory()->withUser($user)->create(['firstname' => 'Revealed', 'father_id' => $child->id, 'yod' => 2020]);

    $query = new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
        FakeDescendantsQuery::row($child, 1, "{$root->id},{$child->id}"),
        FakeDescendantsQuery::row($grandchild, 2, "{$root->id},{$child->id},{$grandchild->id}"),
    ]));

    app()->instance(DescendantsQueryInterface::class, $query);

    Livewire::test(Tree::class, ['person' => $root, 'maxDepth' => 3])
        ->assertSee('Branch')
        ->assertSee('Revealed')
        ->assertSee((string) $child->id, false)
        ->assertSee((string) $grandchild->id, false);

    expect($query->calls)->toHaveCount(1);
});
