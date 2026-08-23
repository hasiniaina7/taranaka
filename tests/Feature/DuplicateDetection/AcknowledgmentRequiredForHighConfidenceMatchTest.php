<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('the high-confidence soft gate is enforced by the server and resets after identity input changes', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1954,
        'dob'       => null,
    ]);
    $personCount = Person::withoutGlobalScope('team')->count();

    $component = Livewire::actingAs($user)
        ->test('people::add.person')
        ->set('form.firstname', 'Jean')
        ->set('form.surname', 'Rakoto')
        ->set('form.sex', 'm')
        ->set('form.yob', '1954')
        ->call('savePerson')
        ->assertHasErrors(['duplicateAcknowledgment']);

    expect(Person::withoutGlobalScope('team')->count())->toBe($personCount);

    $component
        ->call('acknowledgeDuplicateCandidates')
        ->assertSet('requiresDuplicateAcknowledgment', false)
        ->set('form.yob', '1955')
        ->assertSet('requiresDuplicateAcknowledgment', true);
});

test('a new high-confidence candidate invalidates an earlier acknowledgment before saving', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1954,
        'dob'       => null,
    ]);

    $component = Livewire::actingAs($user)
        ->test('people::add.person')
        ->set('form.firstname', 'Jean')
        ->set('form.surname', 'Rakoto')
        ->set('form.sex', 'm')
        ->set('form.yob', '1954')
        ->call('acknowledgeDuplicateCandidates')
        ->assertSet('requiresDuplicateAcknowledgment', false);

    Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1954,
        'dob'       => null,
    ]);

    $personCount = Person::withoutGlobalScope('team')->count();

    $component
        ->call('savePerson')
        ->assertHasErrors(['duplicateAcknowledgment']);

    expect(Person::withoutGlobalScope('team')->count())->toBe($personCount);
});
