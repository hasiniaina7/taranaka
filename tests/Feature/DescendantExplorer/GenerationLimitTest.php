<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Livewire\People\Descendants\Tree;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\Support\FakeDescendantsQuery;

test('the traversal limit hides deeper generations and explains the truncation', function (): void {
    $user       = User::factory()->withPersonalTeam()->create();
    $root       = Person::factory()->withUser($user)->create(['yod' => 1980]);
    $child      = Person::factory()->withUser($user)->create(['father_id' => $root->id, 'yod' => 2000]);
    $grandchild = Person::factory()->withUser($user)->create(['firstname' => 'Last Visible', 'father_id' => $child->id, 'yod' => 2020]);
    $greatGrand = Person::factory()->withUser($user)->create(['firstname' => 'Past Limit', 'father_id' => $grandchild->id, 'yod' => 2040]);
    $query      = new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
        FakeDescendantsQuery::row($child, 1, "{$root->id},{$child->id}"),
        FakeDescendantsQuery::row($grandchild, 2, "{$root->id},{$child->id},{$grandchild->id}"),
        FakeDescendantsQuery::row($greatGrand, 3, "{$root->id},{$child->id},{$grandchild->id},{$greatGrand->id}"),
    ]));

    app()->instance(DescendantsQueryInterface::class, $query);

    Livewire::test(Tree::class, ['person' => $root, 'maxDepth' => 2])
        ->call('toggleNode', $child->id)
        ->assertSee('Last Visible')
        ->assertDontSee('Past Limit')
        ->assertSee('More generations may exist — increase the limit.');

    expect($query->calls)->toContain(['person_id' => $root->id, 'max_depth' => 2]);
});
