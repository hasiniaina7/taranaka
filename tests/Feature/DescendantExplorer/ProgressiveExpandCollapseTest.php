<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Livewire\People\Descendants\Tree;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\Support\FakeDescendantsQuery;

test('a branch expands and collapses through a Livewire action and every visible node links to its profile', function (): void {
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
        ->assertSee(route('public.people.show', $child), escape: false)
        ->assertDontSee('Revealed')
        ->call('toggleNode', $child->id)
        ->assertSee('Revealed')
        ->assertSee(route('public.people.show', $grandchild), escape: false)
        ->call('toggleNode', $child->id)
        ->assertDontSee('Revealed');

    expect($query->calls)->toHaveCount(1);
});
