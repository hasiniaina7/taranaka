<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Lineage;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('each family link and lineage tag on a profile navigates to the correct page', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $father = Person::factory()->withUser($user)->create(['yod' => 1990]);
    $mother = Person::factory()->withUser($user)->create(['yod' => 1992]);

    $person = Person::factory()->withUser($user)->create([
        'father_id' => $father->id,
        'mother_id' => $mother->id,
        'yod'       => 2010,
    ]);

    $partner = Person::factory()->withUser($user)->create(['yod' => 2015]);
    Couple::factory()->create(['person1_id' => $person->id, 'person2_id' => $partner->id, 'team_id' => $user->currentTeam->id]);

    $child = Person::factory()->withUser($user)->create(['father_id' => $person->id, 'yod' => 2020]);

    $lineage = Lineage::factory()->create();
    $lineage->people()->attach($person->id);

    $response = $this->get("/p/{$person->id}");

    $response->assertOk();
    $response->assertSee(route('public.people.show', $father), false);
    $response->assertSee(route('public.people.show', $mother), false);
    $response->assertSee(route('public.people.show', $partner), false);
    $response->assertSee(route('public.people.show', $child), false);
    $response->assertSee(route('lineages.show', $lineage), false);

    $this->get(route('public.people.show', $father))->assertOk();
    $this->get(route('lineages.show', $lineage))->assertOk();
});

test('empty family sections show individual empty-state copy rather than being hidden', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create(['yod' => 2000]);

    $response = $this->get("/p/{$person->id}");

    $response->assertOk();
    $response->assertSee(__('person.no_parents_known'));
    $response->assertSee(__('person.no_partners_recorded'));
    $response->assertSee(__('person.no_children_recorded'));
    $response->assertSee(__('lineage.no_lineages'));
});
