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

test('a guest sees the root and first descendant generation while deeper branches start collapsed', function (): void {
    $user       = User::factory()->withPersonalTeam()->create();
    $root       = Person::factory()->withUser($user)->create(['firstname' => 'Root', 'surname' => 'Person', 'yod' => 1990]);
    $child      = Person::factory()->withUser($user)->create(['firstname' => 'First', 'surname' => 'Generation', 'father_id' => $root->id, 'yod' => 2010]);
    $grandchild = Person::factory()->withUser($user)->create(['firstname' => 'Hidden', 'surname' => 'Grandchild', 'father_id' => $child->id, 'yod' => 2020]);

    app()->instance(DescendantsQueryInterface::class, new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
        FakeDescendantsQuery::row($child, 1, "{$root->id},{$child->id}"),
        FakeDescendantsQuery::row($grandchild, 2, "{$root->id},{$child->id},{$grandchild->id}"),
    ])));

    TestResponse::fromBaseResponse(app(Kernel::class)->handle(Request::create(route('front.people.descendants', $root))))
        ->assertOk()
        ->assertSee('Root Person')
        ->assertSee('First Generation')
        ->assertDontSee('Hidden Grandchild');
});

test('a person without descendants has a clear empty state', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    $root = Person::factory()->withUser($user)->create(['firstname' => 'Solo', 'surname' => 'Root', 'yod' => 1990]);

    app()->instance(DescendantsQueryInterface::class, new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
    ])));

    TestResponse::fromBaseResponse(app(Kernel::class)->handle(Request::create(route('front.people.descendants', $root))))
        ->assertOk()
        ->assertSee('Solo Root')
        ->assertSee('No descendants recorded.');
});

test('an authenticated visitor can open a public root outside their team scope', function (): void {
    $rootOwner = User::factory()->withPersonalTeam()->create();
    $visitor   = User::factory()->withPersonalTeam()->create();
    $root      = Person::factory()->withUser($rootOwner)->create(['firstname' => 'Public', 'surname' => 'Root', 'yod' => 1990]);

    auth()->login($visitor);

    app()->instance(DescendantsQueryInterface::class, new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
    ])));

    TestResponse::fromBaseResponse(app(Kernel::class)->handle(Request::create(route('front.people.descendants', $root))))
        ->assertOk()
        ->assertSee('Public Root');
});
