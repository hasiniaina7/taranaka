<?php

declare(strict_types=1);

use App\Actions\People\RecordDuplicateResolution;
use App\Models\Activity;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('selecting the same person records the decision and creates no person', function (): void {
    $user     = User::factory()->withPersonalTeam()->create();
    $existing = Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1954,
        'dob'       => null,
    ]);
    $personCount = Person::withoutGlobalScope('team')->count();

    Livewire::actingAs($user)
        ->test('people::add.person')
        ->set('form.firstname', 'Jean')
        ->set('form.surname', 'Rakoto')
        ->set('form.yob', '1954')
        ->call('reuseExistingPerson', $existing->id)
        ->assertRedirect(route('people.show', $existing));

    expect(Person::withoutGlobalScope('team')->count())->toBe($personCount);

    $activity = Activity::query()
        ->where('event', RecordDuplicateResolution::LINKED_AS_SAME)
        ->where('subject_type', Person::class)
        ->where('subject_id', $existing->id)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity?->properties->get('resolution'))->toBe(RecordDuplicateResolution::LINKED_AS_SAME);
});
