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

    // Since spec 012, node privacy is a payload property (is_living, no exact
    // dob/dod) consumed by the client-side canvas rather than a server-
    // rendered "private" badge string.
    $tree = Livewire::test(Tree::class, ['person' => $root, 'maxDepth' => 3])
        ->assertSee('Living Descendant')
        ->instance()
        ->tree();

    $livingNode = collect($tree['children'])->firstWhere('id', $living->id);

    expect($livingNode['is_living'])->toBeTrue();
    expect($livingNode)->not->toHaveKey('dob');
    expect($livingNode)->not->toHaveKey('dod');
});
