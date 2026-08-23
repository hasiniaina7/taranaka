<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;

test('every list endpoint returns bounded pagination metadata', function (): void {
    $root   = Person::factory()->create(['firstname' => 'PaginationRoot', 'yod' => 1980]);
    $parent = Person::factory()->create(['firstname' => 'PaginationParent', 'yod' => 1950]);
    $root->forceFill(['father_id' => $parent->id])->save();
    $members = Person::factory()->count(3)->create(['yod' => 2000]);
    $lineage = Lineage::factory()->create(['name' => 'Pagination Lineage']);
    $lineage->people()->attach($members->pluck('id'));

    Person::factory()->count(3)->create([
        'surname'   => 'PaginationNeedle',
        'father_id' => $root->id,
        'yod'       => 2010,
    ]);

    foreach ([
        route('api.v1.persons.descendants', ['person' => $root->id, 'per_page' => 2]),
        route('api.v1.persons.ancestors', ['person' => $root->id, 'per_page' => 2]),
        route('api.v1.lineages.members', ['lineage' => $lineage->id, 'per_page' => 2]),
    ] as $url) {
        test()->getJson($url)
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.per_page', 2);
    }

    test()->getJson(route('api.v1.search', ['q' => 'PaginationNeedle', 'per_page' => 2]))
        ->assertOk()
        ->assertJsonStructure([
            'people'   => ['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']],
            'lineages' => ['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']],
        ])
        ->assertJsonPath('people.meta.per_page', 2)
        ->assertJsonPath('lineages.meta.per_page', 2);
});

test('list endpoints enforce their documented defaults', function (): void {
    $person  = Person::factory()->create(['yod' => 2000]);
    $lineage = Lineage::factory()->create(['name' => 'Defaults Lineage']);

    test()->getJson(route('api.v1.persons.descendants', $person))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);

    test()->getJson(route('api.v1.persons.ancestors', $person))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);

    test()->getJson(route('api.v1.lineages.members', $lineage))
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);

    test()->getJson(route('api.v1.search', ['q' => 'Defaults']))
        ->assertOk()
        ->assertJsonPath('people.meta.per_page', 10)
        ->assertJsonPath('lineages.meta.per_page', 10);
});
