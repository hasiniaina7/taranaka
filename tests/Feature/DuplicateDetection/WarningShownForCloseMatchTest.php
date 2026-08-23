<?php

declare(strict_types=1);

use App\Livewire\People\DuplicateWarningPanel;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('the creation form shows a scored warning before saving a close match', function (): void {
    $user     = User::factory()->withPersonalTeam()->create();
    $existing = Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1954,
        'dob'       => null,
        'dod'       => '2020-01-01',
    ]);

    $component = Livewire::actingAs($user)
        ->test('people::add.person')
        ->set('form.firstname', 'Jean')
        ->set('form.surname', 'Rakoto')
        ->set('form.yob', '1954');

    $candidates = $component->get('duplicateCandidates');

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]['id'])->toBe($existing->id)
        ->and($candidates[0]['score'])->toBe(1.0)
        ->and($component->get('requiresDuplicateAcknowledgment'))->toBeTrue();

    Livewire::actingAs($user)
        ->test(DuplicateWarningPanel::class, ['candidates' => $candidates])
        ->assertSee('Jean Rakoto')
        ->assertSee('100 %');
});
