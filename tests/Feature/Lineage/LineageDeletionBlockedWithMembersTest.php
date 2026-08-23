<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('deleting a lineage with attached people is blocked', function (): void {
    $user    = User::factory()->withPersonalTeam()->create();
    $lineage = Lineage::factory()->create();
    $person  = Person::factory()->create(['team_id' => $user->currentTeam->id]);
    $lineage->people()->attach($person);

    Livewire::actingAs($user)
        ->test('lineages::manage')
        ->call('delete', $lineage->id);

    $this->assertModelExists($lineage);
});

test('deleting a lineage with zero members succeeds', function (): void {
    $user    = User::factory()->withPersonalTeam()->create();
    $lineage = Lineage::factory()->create();

    Livewire::actingAs($user)
        ->test('lineages::manage')
        ->call('delete', $lineage->id);

    $this->assertModelMissing($lineage);
});
