<?php

declare(strict_types=1);

use App\Models\Couple;
use App\Models\Lineage;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a signed-out visitor can view a deceased person profile with no login redirect', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $father = Person::factory()->withUser($user)->create(['firstname' => 'Jean', 'surname' => 'RAKOTO', 'yod' => 1990]);
    $mother = Person::factory()->withUser($user)->create(['firstname' => 'Marie', 'surname' => 'RABE', 'yod' => 1992]);

    $person = Person::factory()->withUser($user)->create([
        'firstname' => 'Paul',
        'surname'   => 'RAKOTO',
        'father_id' => $father->id,
        'mother_id' => $mother->id,
        'yob'       => 1950,
        'yod'       => 2010,
    ]);

    $partner = Person::factory()->withUser($user)->create(['firstname' => 'Alice', 'surname' => 'RABE', 'yod' => 2015]);
    Couple::factory()->create(['person1_id' => $person->id, 'person2_id' => $partner->id, 'team_id' => $user->currentTeam->id]);

    $child = Person::factory()->withUser($user)->create(['firstname' => 'Sofia', 'surname' => 'RAKOTO', 'father_id' => $person->id, 'yod' => 2020]);

    $lineage = Lineage::factory()->create(['name' => 'RAKOTO']);
    $lineage->people()->attach($person->id);

    $response = $this->get("/p/{$person->id}");

    $response->assertOk();
    $response->assertSee($person->firstname);
    $response->assertSee($father->firstname);
    $response->assertSee($mother->firstname);
    $response->assertSee($partner->firstname);
    $response->assertSee($child->firstname);
    $response->assertSee($lineage->name);
});

test('the public profile route is distinct from the authenticated people.show route', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create(['yod' => 2000]);

    $this->get("/p/{$person->id}")->assertOk();
    $this->get("/people/{$person->id}")->assertRedirect('/login');
});
