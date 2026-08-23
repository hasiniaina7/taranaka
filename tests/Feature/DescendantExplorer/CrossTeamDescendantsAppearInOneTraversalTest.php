<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Livewire\People\Descendants\Tree;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('the descendant query keeps following relationships across team boundaries', function (): void {
    $firstOwner  = User::factory()->withPersonalTeam()->create();
    $secondOwner = User::factory()->withPersonalTeam()->create();
    $parent      = Person::factory()->withUser($firstOwner)->create();
    $child       = Person::factory()->withUser($secondOwner)->create(['firstname' => 'Cross Team Child', 'father_id' => $parent->id]);
    $grandchild  = Person::factory()->withUser($secondOwner)->create(['firstname' => 'Cross Team Grandchild', 'father_id' => $child->id]);

    auth()->login($firstOwner);

    $descendantIds = app(DescendantsQueryInterface::class)
        ->getDescendants($parent->id, 3)
        ->pluck('id');

    expect($descendantIds)->toContain($child->id, $grandchild->id);

    Livewire::test(Tree::class, ['person' => $parent, 'maxDepth' => 3])
        ->assertSee('Cross Team Child')
        ->call('toggleNode', $child->id)
        ->assertSee('Cross Team Grandchild');
});
