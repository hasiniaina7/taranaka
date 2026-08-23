<?php

declare(strict_types=1);

use App\Contracts\AncestorsQueryInterface;
use App\Models\Person;

test('ancestors match the shared bounded traversal without returning its root', function (): void {
    $grandfather = Person::factory()->create(['firstname' => 'Grandfather', 'yod' => 1980]);
    $father      = Person::factory()->create(['firstname' => 'Father', 'father_id' => $grandfather->id, 'yod' => 2010]);
    $person      = Person::factory()->create(['firstname' => 'Root', 'father_id' => $father->id, 'yod' => 2020]);

    $expected = app(AncestorsQueryInterface::class)
        ->getAncestors($person->id, 2)
        ->where('degree', '>', 0)
        ->map(fn (object $row): array => ['id' => (int) $row->id, 'degree' => (int) $row->degree])
        ->values()
        ->all();

    $response = test()->getJson(route('api.v1.persons.ancestors', [
        'person'    => $person->id,
        'max_depth' => 2,
    ]));

    $actual = collect($response->assertOk()->json('data'))
        ->map(fn (array $row): array => ['id' => $row['id'], 'degree' => $row['degree']])
        ->all();

    expect($actual)->toBe($expected)
        ->and(collect($actual)->pluck('id')->all())->toBe([$father->id, $grandfather->id]);
});

test('ancestor bounds are validated', function (array $query): void {
    $person = Person::factory()->create(['yod' => 2000]);

    test()->getJson(route('api.v1.persons.ancestors', ['person' => $person->id, ...$query]))
        ->assertUnprocessable();
})->with([
    'depth below one'        => [['max_depth' => 0]],
    'depth above engine cap' => [['max_depth' => 129]],
    'per page above API cap' => [['per_page' => 501]],
]);
