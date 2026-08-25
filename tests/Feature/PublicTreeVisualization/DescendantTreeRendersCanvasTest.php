<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakeDescendantsQuery;

test('the public descendant tree page renders the family-tree canvas with the tree payload', function (): void {
    $user  = User::factory()->withPersonalTeam()->create();
    $root  = Person::factory()->withUser($user)->create(['firstname' => 'Canvas', 'surname' => 'Root', 'yod' => 1990]);
    $child = Person::factory()->withUser($user)->create(['firstname' => 'Canvas', 'surname' => 'Child', 'father_id' => $root->id, 'yod' => 2010]);

    app()->instance(DescendantsQueryInterface::class, new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
        FakeDescendantsQuery::row($child, 1, "{$root->id},{$child->id}"),
    ])));

    TestResponse::fromBaseResponse(app(Kernel::class)->handle(Request::create(route('front.people.descendants', $root))))
        ->assertOk()
        ->assertSee('family-tree-canvas', false)
        ->assertSee('familyTreeCanvas', false)
        ->assertSee('Canvas Root')
        ->assertSee('Canvas Child');
});
