<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('detaching a person from one lineage leaves the other membership and biographical data intact', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->create(['team_id' => $user->currentTeam->id, 'firstname' => 'Jean']);
    $rakoto = Lineage::factory()->create(['name' => 'RAKOTO']);
    $rabe   = Lineage::factory()->create(['name' => 'RABE']);

    $person->lineages()->attach([$rakoto->id, $rabe->id]);

    Livewire::actingAs($user)
        ->test('people::person-lineage-manager', ['person' => $person])
        ->call('detach', $rakoto->id)
        ->assertHasNoErrors();

    $person->refresh();

    expect($person->lineages)->toHaveCount(1)
        ->and($person->lineages->first()->id)->toBe($rabe->id)
        ->and($person->firstname)->toBe('Jean');
});
