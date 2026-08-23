<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('candidate search crosses team ownership and uses a route the contributor can open', function (): void {
    $contributor = User::factory()->withPersonalTeam()->create();
    $otherOwner  = User::factory()->withPersonalTeam()->create();
    $lineage     = Lineage::factory()->create(['name' => 'Private Rakoto Lineage']);
    $existing    = Person::factory()->withUser($otherOwner)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1954,
        'dob'       => '1954-04-12',
        'pob'       => 'Antananarivo',
        'photo'     => 'private-photo',
        'dod'       => null,
        'yod'       => null,
    ]);
    $existing->lineages()->attach($lineage);

    $component = Livewire::actingAs($contributor)
        ->test('people::add.person')
        ->set('form.firstname', 'Jean')
        ->set('form.surname', 'Rakoto')
        ->set('form.yob', '1954');

    $candidate = $component->get('duplicateCandidates')[0];

    expect($component->get('duplicateCandidates'))->toHaveCount(1)
        ->and($candidate['id'])->toBe($existing->id)
        ->and($candidate['url'])->toBe(route('public.people.show', $existing))
        ->and($candidate['private'])->toBeTrue()
        ->and($candidate['lifespan'])->toBeNull()
        ->and($candidate['lineages'])->toBe([])
        ->and($candidate)->not->toHaveKeys(['dob', 'pob', 'photo', 'birth_year']);

    $component
        ->call('reuseExistingPerson', $existing->id)
        ->assertRedirect(route('public.people.show', $existing));
});
