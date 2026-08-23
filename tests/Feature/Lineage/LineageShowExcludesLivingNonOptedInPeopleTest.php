<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('the public lineage page only shows deceased people, never living non-opted-in ones', function (): void {
    $lineage  = Lineage::factory()->create();
    $deceased = Person::factory()->create(['firstname' => 'Deceased', 'yod' => 2000]);
    $living   = Person::factory()->create(['firstname' => 'Living', 'yod' => null, 'dod' => null]);

    $lineage->people()->attach([$deceased->id, $living->id]);

    $response = $this->get(route('lineages.show', $lineage));

    $response->assertOk();
    $response->assertSee($deceased->name);
    $response->assertDontSee($living->name);
});
