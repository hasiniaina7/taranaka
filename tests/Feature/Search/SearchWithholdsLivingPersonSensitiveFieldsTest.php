<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;

test('living person results expose only their name and a private indicator', function (): void {
    $privateLineage = Lineage::factory()->create(['name' => 'Private Search Lineage']);
    $person         = Person::factory()->create([
        'firstname'   => 'Livingneedle',
        'surname'     => 'Confidential',
        'dob'         => '1990-06-15',
        'yob'         => 1990,
        'dod'         => null,
        'yod'         => null,
        'street'      => 'Secret Search Street',
        'postal_code' => '75000',
        'city'        => 'Hidden Search City',
        'phone'       => '0102030405',
    ]);

    $person->lineages()->attach($privateLineage);

    test()->get(route('public.search', ['q' => 'Livingneedle']))
        ->assertOk()
        ->assertSee($person->name)
        ->assertSee('Profil privé')
        ->assertDontSee('1990-06-15')
        ->assertDontSee('1990 -')
        ->assertDontSee('Secret Search Street')
        ->assertDontSee('75000')
        ->assertDontSee('Hidden Search City')
        ->assertDontSee('0102030405')
        ->assertDontSee('Private Search Lineage');
});
