<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a lineage page lists all its deceased members', function (): void {
    $lineage = Lineage::factory()->create(['name' => 'RAKOTO']);
    $people  = Person::factory()->count(5)->create(['yod' => 1990]);
    $lineage->people()->attach($people->pluck('id'));

    $response = $this->get(route('lineages.show', $lineage));

    $response->assertOk();

    foreach ($people as $person) {
        $response->assertSee($person->name);
    }
});

test('a lineage page shows an empty state when it has no members', function (): void {
    $lineage = Lineage::factory()->create(['name' => 'RABE']);

    $this->get(route('lineages.show', $lineage))
        ->assertOk()
        ->assertSee(__('lineage.no_members'));
});
