<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

test('a guest can open the ancestor explorer with the root and first generation visible', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $grandfather = Person::factory()->withUser($user)->create([
        'firstname' => 'Grandfather',
        'yod'       => 1980,
    ]);
    $father = Person::factory()->withUser($user)->create([
        'firstname' => 'Father',
        'father_id' => $grandfather->id,
        'yod'       => 2000,
    ]);
    $mother = Person::factory()->withUser($user)->create([
        'firstname' => 'Mother',
        'yod'       => 2001,
    ]);
    $person = Person::factory()->withUser($user)->create([
        'firstname' => 'Root Person',
        'father_id' => $father->id,
        'mother_id' => $mother->id,
        'yod'       => 2020,
    ]);

    // Since spec 012, profile navigation is resolved client-side by
    // family-tree.js from each node's `personId` (against a profile URL
    // template), not rendered as a server-side <a href> per node.
    test()->get(route('front.people.ancestors', $person))
        ->assertOk()
        ->assertSee('Root Person')
        ->assertSee('Father')
        ->assertSee('Mother')
        ->assertDontSee('Grandfather')
        ->assertSee((string) $father->id, false);
});
