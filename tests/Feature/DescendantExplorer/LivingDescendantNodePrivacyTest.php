<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Livewire\People\Descendants\Tree;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\Support\FakeDescendantsQuery;

test('a living descendant is marked private without exposing an exact birth date', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $root   = Person::factory()->withUser($user)->create(['yod' => 1990]);
    $living = Person::factory()->withUser($user)->create([
        'firstname' => 'Living',
        'surname'   => 'Descendant',
        'father_id' => $root->id,
        'dob'       => '2001-02-03',
        'yob'       => 2001,
        'dod'       => null,
        'yod'       => null,
    ]);

    app()->instance(DescendantsQueryInterface::class, new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
        FakeDescendantsQuery::row($living, 1, "{$root->id},{$living->id}"),
    ])));

    Livewire::test(Tree::class, ['person' => $root, 'maxDepth' => 3])
        ->assertSee('Living Descendant')
        ->assertSee(__('person.profile_private'))
        ->assertDontSee('2001-02-03');
});
