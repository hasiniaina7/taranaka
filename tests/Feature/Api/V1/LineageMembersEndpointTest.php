<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;

test('lineage detail and members mirror the public lineage surface', function (): void {
    $lineage = Lineage::factory()->create([
        'name'        => 'Rakoto',
        'description' => 'A public lineage.',
    ]);
    $deceased = Person::factory()->create(['firstname' => 'Deceased', 'yod' => 2000]);
    $optedIn  = Person::factory()->create([
        'firstname'           => 'Visible',
        'yod'                 => null,
        'dod'                 => null,
        'is_publicly_visible' => true,
    ]);
    $private = Person::factory()->create([
        'firstname'           => 'Hidden',
        'yod'                 => null,
        'dod'                 => null,
        'is_publicly_visible' => false,
    ]);
    $lineage->people()->attach([$deceased->id, $optedIn->id, $private->id]);

    test()->getJson(route('api.v1.lineages.show', $lineage))
        ->assertOk()
        ->assertJsonPath('data.id', $lineage->id)
        ->assertJsonPath('data.name', 'Rakoto')
        ->assertJsonPath('data.description', 'A public lineage.')
        ->assertJsonPath('data.member_count', 3);

    $members = test()->getJson(route('api.v1.lineages.members', $lineage))
        ->assertOk()
        ->json('data');

    expect(collect($members)->pluck('id')->all())
        ->toContain($deceased->id, $optedIn->id)
        ->not->toContain($private->id);
});

test('missing lineages use the common not found contract', function (): void {
    test()->getJson(route('api.v1.lineages.show', 999999))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Not found.']);
});
