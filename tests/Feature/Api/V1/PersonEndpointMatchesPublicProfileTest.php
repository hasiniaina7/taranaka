<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Lineage;
use App\Models\Person;
use App\Models\User;

test('a deceased person endpoint exposes the same public profile graph', function (): void {
    $father = Person::factory()->create(['firstname' => 'Paul', 'surname' => 'Rakoto', 'yod' => 1990]);
    $mother = Person::factory()->create(['firstname' => 'Marie', 'surname' => 'Rabe', 'yod' => 1992]);
    $person = Person::factory()->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'father_id' => $father->id,
        'mother_id' => $mother->id,
        'dob'       => '1930-05-01',
        'yob'       => 1930,
        'yod'       => 2003,
        'phone'     => '0102030405',
    ]);
    $partner = Person::factory()->create(['firstname' => 'Alice', 'surname' => 'Rabe', 'yod' => 2010]);
    $child   = Person::factory()->create(['firstname' => 'Sofia', 'surname' => 'Rakoto', 'father_id' => $person->id, 'yod' => 2020]);
    $lineage = Lineage::factory()->create(['name' => 'Rakoto']);

    Couple::factory()->create([
        'person1_id' => $person->id,
        'person2_id' => $partner->id,
        'team_id'    => null,
    ]);
    $person->lineages()->attach($lineage);

    $response = test()->getJson(route('api.v1.persons.show', $person));

    $response->assertOk()
        ->assertJsonPath('data.id', $person->id)
        ->assertJsonPath('data.name', 'Jean Rakoto')
        ->assertJsonPath('data.lineages.0.id', $lineage->id)
        ->assertJsonPath('data.parents.0.id', $father->id)
        ->assertJsonPath('data.parents.1.id', $mother->id)
        ->assertJsonPath('data.partners.0.id', $partner->id)
        ->assertJsonPath('data.children.0.id', $child->id)
        ->assertJsonPath('data.is_private', false);

    expect($response->json('data'))->not->toHaveKeys([
        'dob',
        'dod',
        'street',
        'number',
        'postal_code',
        'city',
        'province',
        'state',
        'country',
        'phone',
    ]);

    test()->get(route('public.people.show', $person))
        ->assertOk()
        ->assertSee('Jean')
        ->assertSee('Paul')
        ->assertSee('Marie')
        ->assertSee('Alice')
        ->assertSee('Sofia')
        ->assertSee('Rakoto');
});

test('a living non opted in person omits sensitive keys rather than returning nulls', function (): void {
    $person = Person::factory()->create([
        'firstname'           => 'Living',
        'surname'             => 'Private',
        'dob'                 => '1990-06-15',
        'dod'                 => null,
        'yod'                 => null,
        'street'              => 'Secret Street',
        'phone'               => '0102030405',
        'is_publicly_visible' => false,
    ]);

    $response = test()->getJson(route('api.v1.persons.show', $person));
    $data     = $response->assertOk()->json('data');

    expect($data)->toBeArray()
        ->and($data['is_private'])->toBeTrue();

    foreach (['dob', 'dod', 'street', 'number', 'postal_code', 'city', 'province', 'state', 'country', 'phone'] as $sensitiveKey) {
        expect(array_key_exists($sensitiveKey, $data))->toBeFalse();
    }

    test()->get(route('public.people.show', $person))
        ->assertOk()
        ->assertDontSee('1990-06-15')
        ->assertDontSee('Secret Street')
        ->assertDontSee('0102030405');
});

test('the public API ignores an authenticated callers team scope', function (): void {
    $caller = User::factory()->withPersonalTeam()->create();
    $owner  = User::factory()->withPersonalTeam()->create();
    $father = Person::factory()->withUser($owner)->create(['firstname' => 'CrossTeamFather', 'yod' => 1980]);
    $person = Person::factory()->withUser($owner)->create(['father_id' => $father->id, 'yod' => 2000]);

    test()->actingAs($caller);

    test()->getJson(route('api.v1.persons.show', $person))
        ->assertOk()
        ->assertJsonPath('data.id', $person->id)
        ->assertJsonPath('data.parents.0.id', $father->id);
});

test('missing and soft deleted people share the same not found response', function (): void {
    $person = Person::factory()->create();
    $person->delete();

    test()->getJson(route('api.v1.persons.show', $person->id))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Not found.']);

    test()->getJson(route('api.v1.persons.show', 999999))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Not found.']);
});
