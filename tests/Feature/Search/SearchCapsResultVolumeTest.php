<?php

declare(strict_types=1);

use App\Models\Person;

test('person results are capped and invite visitors to refine broad searches', function (): void {
    foreach (range(1, 11) as $sequence) {
        Person::factory()->create([
            'firstname' => 'Capneedle',
            'surname'   => sprintf('Person%02d', $sequence),
            'yod'       => 2000,
        ]);
    }

    test()->get(route('public.search', ['q' => 'Capneedle']))
        ->assertOk()
        ->assertSee('Capneedle Person01')
        ->assertSee('Capneedle Person10')
        ->assertDontSee('Capneedle Person11')
        ->assertSee('Affiner votre recherche pour réduire les résultats.');
});
