<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;

test('percent and underscore characters in a search term are treated literally', function (): void {
    $literalPerson = Person::factory()->create([
        'firstname' => 'Literal_100%',
        'surname'   => 'Match',
        'yod'       => 2000,
    ]);
    $broadPerson = Person::factory()->create([
        'firstname' => 'LiteralX1000',
        'surname'   => 'Mismatch',
        'yod'       => 2000,
    ]);

    Lineage::factory()->create(['name' => 'Literal_100% Family']);
    Lineage::factory()->create(['name' => 'LiteralX1000 Family']);

    test()->get(route('public.search', ['q' => 'Literal_100%']))
        ->assertOk()
        ->assertSee($literalPerson->name)
        ->assertDontSee($broadPerson->name)
        ->assertSee('Literal_100% Family')
        ->assertDontSee('LiteralX1000 Family');
});
