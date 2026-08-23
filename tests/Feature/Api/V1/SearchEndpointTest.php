<?php

declare(strict_types=1);

use App\Actions\Search\FindPublicLineages;
use App\Actions\Search\FindPublicPeople;
use App\Models\Lineage;
use App\Models\Person;

test('search reuses the same public person and lineage projections as the web', function (): void {
    $lineage = Lineage::factory()->create(['name' => 'Needle Lineage']);
    $person  = Person::factory()->create([
        'firstname' => 'Needle',
        'surname'   => 'Person',
        'yod'       => 2000,
    ]);
    $person->lineages()->attach($lineage);

    $expectedPeople   = app(FindPublicPeople::class)->paginated('Needle', 10, 'page')->items();
    $expectedLineages = app(FindPublicLineages::class)->paginated('Needle', 10, 'page')->items();

    test()->getJson(route('api.v1.search', ['q' => 'Needle']))
        ->assertOk()
        ->assertJsonPath('people.data', $expectedPeople)
        ->assertJsonPath('lineages.data', $expectedLineages)
        ->assertJsonPath('people.data.0.id', $person->id)
        ->assertJsonPath('lineages.data.0.id', $lineage->id);
});

test('search keeps the web privacy projection for living people', function (): void {
    $person = Person::factory()->create([
        'firstname'           => 'Livingneedle',
        'surname'             => 'Private',
        'dob'                 => '1990-06-15',
        'street'              => 'Secret Street',
        'phone'               => '0102030405',
        'dod'                 => null,
        'yod'                 => null,
        'is_publicly_visible' => false,
    ]);

    $result = test()->getJson(route('api.v1.search', ['q' => 'Livingneedle']))
        ->assertOk()
        ->assertJsonPath('people.data.0.id', $person->id)
        ->assertJsonPath('people.data.0.private', true)
        ->json('people.data.0');

    expect($result)->not->toHaveKeys(['dob', 'street', 'phone'])
        ->and($result['lifespan'])->toBeNull()
        ->and($result['lineages'])->toBe([]);
});

test('search input and pagination bounds are validated', function (array $query): void {
    test()->getJson(route('api.v1.search', $query))->assertUnprocessable();
})->with([
    'missing query'                       => [[]],
    'short query'                         => [['q' => 'a']],
    'long query'                          => [['q' => str_repeat('a', 101)]],
    'markup cannot bypass minimum length' => [['q' => '<b>a</b>']],
    'oversized page'                      => [['q' => 'valid', 'per_page' => 501]],
]);
