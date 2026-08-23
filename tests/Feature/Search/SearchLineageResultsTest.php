<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;

test('person and lineage matches appear in separately labelled sections', function (): void {
    $lineage = Lineage::factory()->create(['name' => 'Rakoto']);
    $person  = Person::factory()->create([
        'firstname' => 'Jeanne',
        'surname'   => 'Rakoto',
        'yod'       => 2001,
    ]);

    $lineage->people()->attach($person);

    test()->get(route('public.search', ['q' => 'Rakoto']))
        ->assertOk()
        ->assertSeeInOrder(['Personnes', 'Jeanne Rakoto', 'Lignées', 'Rakoto'])
        ->assertSee(route('public.people.show', $person), escape: false)
        ->assertSee(route('lineages.show', $lineage), escape: false);
});
