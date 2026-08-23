<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;

test('same-name people are disambiguated by lifespan and lineage', function (): void {
    $olderLineage   = Lineage::factory()->create(['name' => 'Rakoto Nord']);
    $youngerLineage = Lineage::factory()->create(['name' => 'Rakoto Sud']);

    $older = Person::factory()->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'dob'       => null,
        'dod'       => null,
        'yob'       => 1920,
        'yod'       => 1980,
    ]);
    $younger = Person::factory()->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'dob'       => null,
        'dod'       => null,
        'yob'       => 1950,
        'yod'       => 2015,
    ]);

    $older->lineages()->attach($olderLineage);
    $younger->lineages()->attach($youngerLineage);

    test()->get(route('public.search', ['q' => 'Jean Rakoto']))
        ->assertOk()
        ->assertSee('Jean Rakoto', escape: true)
        ->assertSee('1920 - 1980')
        ->assertSee('1950 - 2015')
        ->assertSee('Rakoto Nord')
        ->assertSee('Rakoto Sud');
});

test('search distinguishes the empty prompt from a query with no matches', function (): void {
    test()->get(route('public.search'))
        ->assertOk()
        ->assertSee('Saisissez un nom pour commencer votre recherche.')
        ->assertDontSee('Aucun résultat pour');

    test()->get(route('public.search', ['q' => 'NoMatchingGenealogyRecord']))
        ->assertOk()
        ->assertSee('Aucun résultat pour « NoMatchingGenealogyRecord ».');
});
