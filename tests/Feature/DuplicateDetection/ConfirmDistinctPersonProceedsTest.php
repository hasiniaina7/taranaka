<?php

declare(strict_types=1);

use App\Actions\People\RecordDuplicateResolution;
use App\Models\Activity;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('acknowledging a distinct person permits creation and records every dismissed candidate', function (): void {
    $user     = User::factory()->withPersonalTeam()->create();
    $existing = Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1954,
        'dob'       => null,
    ]);

    Livewire::actingAs($user)
        ->test('people::add.person')
        ->set('form.firstname', 'Jean')
        ->set('form.surname', 'Rakoto')
        ->set('form.sex', 'm')
        ->set('form.yob', '1954')
        ->call('acknowledgeDuplicateCandidates')
        ->assertSet('requiresDuplicateAcknowledgment', false)
        ->call('savePerson')
        ->assertHasNoErrors();

    $created = Person::withoutGlobalScope('team')
        ->where('firstname', 'Jean')
        ->where('surname', 'Rakoto')
        ->whereKeyNot($existing->id)
        ->first();

    expect($created)->not->toBeNull();

    $activity = Activity::query()
        ->where('event', RecordDuplicateResolution::CONFIRMED_DISTINCT)
        ->where('subject_type', Person::class)
        ->where('subject_id', $created?->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity?->properties->get('resolution'))->toBe(RecordDuplicateResolution::CONFIRMED_DISTINCT)
        ->and($activity?->properties->get('candidates'))->toEqual([
            ['id' => $existing->id, 'score' => 1.0],
        ]);
});
