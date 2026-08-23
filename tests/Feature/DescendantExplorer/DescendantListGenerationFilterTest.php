<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Livewire\People\Descendants\ListView;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Tests\Support\FakeDescendantsQuery;

test('the list filters descendants by generation and name', function (): void {
    $user       = User::factory()->withPersonalTeam()->create();
    $root       = Person::factory()->withUser($user)->create(['yod' => 1990]);
    $child      = Person::factory()->withUser($user)->create(['firstname' => 'Alpha Child', 'father_id' => $root->id, 'yod' => 2010]);
    $grandchild = Person::factory()->withUser($user)->create(['firstname' => 'Beta Grandchild', 'father_id' => $child->id, 'yod' => 2020]);

    $query = new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
        FakeDescendantsQuery::row($child, 1, "{$root->id},{$child->id}"),
        FakeDescendantsQuery::row($grandchild, 2, "{$root->id},{$child->id},{$grandchild->id}"),
    ]));

    app()->instance(DescendantsQueryInterface::class, $query);

    Livewire::test(ListView::class, ['person' => $root, 'maxDepth' => 3])
        ->set('generationFilter', '2')
        ->assertDontSee('Alpha Child')
        ->assertSee('Beta Grandchild')
        ->set('generationFilter', '')
        ->set('nameFilter', 'alpha')
        ->assertSee('Alpha Child')
        ->assertDontSee('Beta Grandchild');

    expect($query->calls)->toHaveCount(1);
});
