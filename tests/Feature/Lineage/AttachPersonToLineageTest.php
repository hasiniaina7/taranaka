<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a person can be attached to multiple lineages without duplicating the person', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->create(['team_id' => $user->currentTeam->id]);
    $rakoto = Lineage::factory()->create(['name' => 'RAKOTO']);
    $rabe   = Lineage::factory()->create(['name' => 'RABE']);

    Livewire::actingAs($user)
        ->test('people::person-lineage-manager', ['person' => $person])
        ->call('attach', $rakoto->id)
        ->assertHasNoErrors();

    Livewire::actingAs($user)
        ->test('people::person-lineage-manager', ['person' => $person])
        ->call('attach', $rabe->id)
        ->assertHasNoErrors();

    expect($person->fresh()->lineages)->toHaveCount(2);
    expect(Person::query()->where('firstname', $person->firstname)->where('surname', $person->surname)->count())->toBe(1);
});

test('re-attaching an already-attached person is idempotent', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->create(['team_id' => $user->currentTeam->id]);
    $rakoto = Lineage::factory()->create(['name' => 'RAKOTO']);

    $person->lineages()->attach($rakoto);

    Livewire::actingAs($user)
        ->test('people::person-lineage-manager', ['person' => $person])
        ->call('attach', $rakoto->id)
        ->assertHasNoErrors();

    expect($person->fresh()->lineages)->toHaveCount(1);
});
