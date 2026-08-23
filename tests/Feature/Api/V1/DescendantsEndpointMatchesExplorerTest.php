<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Models\Person;
use App\Models\User;

test('descendants match the shared bounded traversal without returning its root', function (): void {
    $root       = Person::factory()->create(['firstname' => 'Root', 'yod' => 1980]);
    $child      = Person::factory()->create(['firstname' => 'Child', 'father_id' => $root->id, 'yod' => 2010]);
    $grandchild = Person::factory()->create(['firstname' => 'Grandchild', 'father_id' => $child->id, 'yod' => 2020]);
    Person::factory()->create(['firstname' => 'TooDeep', 'father_id' => $grandchild->id, 'yod' => 2022]);

    $expected = app(DescendantsQueryInterface::class)
        ->getDescendants($root->id, 2)
        ->where('degree', '>', 0)
        ->map(fn (object $row): array => ['id' => (int) $row->id, 'degree' => (int) $row->degree])
        ->values()
        ->all();

    $response = test()->getJson(route('api.v1.persons.descendants', [
        'person'    => $root->id,
        'max_depth' => 2,
    ]));

    $actual = collect($response->assertOk()->json('data'))
        ->map(fn (array $row): array => ['id' => $row['id'], 'degree' => $row['degree']])
        ->all();

    expect($actual)->toBe($expected)
        ->and(collect($actual)->pluck('id')->all())->toBe([$child->id, $grandchild->id]);
});

test('descendant traversal and related references bypass the callers team scope', function (): void {
    $caller = User::factory()->withPersonalTeam()->create();
    $owner  = User::factory()->withPersonalTeam()->create();
    $root   = Person::factory()->withUser($owner)->create(['yod' => 1980]);
    $child  = Person::factory()->withUser($owner)->create(['father_id' => $root->id, 'yod' => 2010]);

    test()->actingAs($caller);

    test()->getJson(route('api.v1.persons.descendants', $root))
        ->assertOk()
        ->assertJsonPath('data.0.id', $child->id);
});

test('descendant bounds are validated', function (array $query): void {
    $person = Person::factory()->create(['yod' => 2000]);

    test()->getJson(route('api.v1.persons.descendants', ['person' => $person->id, ...$query]))
        ->assertUnprocessable();
})->with([
    'depth below one'        => [['max_depth' => 0]],
    'depth above web cap'    => [['max_depth' => 11]],
    'per page above API cap' => [['per_page' => 501]],
]);
