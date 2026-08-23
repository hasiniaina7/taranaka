<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Livewire\People\Descendants\ListView;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\Support\FakeDescendantsQuery;

test('the list shows every descendant with a generation and profile link', function (): void {
    $user       = User::factory()->withPersonalTeam()->create();
    $root       = Person::factory()->withUser($user)->create(['yod' => 1990]);
    $child      = Person::factory()->withUser($user)->create(['firstname' => 'Listed Child', 'father_id' => $root->id, 'yod' => 2010]);
    $grandchild = Person::factory()->withUser($user)->create(['firstname' => 'Listed Grandchild', 'father_id' => $child->id, 'yod' => 2020]);

    app()->instance(DescendantsQueryInterface::class, new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
        FakeDescendantsQuery::row($child, 1, "{$root->id},{$child->id}"),
        FakeDescendantsQuery::row($grandchild, 2, "{$root->id},{$child->id},{$grandchild->id}"),
    ])));

    Livewire::test(ListView::class, ['person' => $root, 'maxDepth' => 3])
        ->assertSee('Listed Child')
        ->assertSee('Listed Grandchild')
        ->assertSee('Generation 1')
        ->assertSee('Generation 2')
        ->assertSee(route('public.people.show', $child), escape: false)
        ->assertSee(route('public.people.show', $grandchild), escape: false);
});
