<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use App\Support\PersonPrivacy;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('an authorized editor toggling is_publicly_visible reveals the person publicly', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'yod'    => null,
        'dod'    => null,
        'street' => 'Secret Street',
    ]);

    $this->actingAs($user);

    Livewire\Livewire::test('people::edit.privacy', ['person' => $person])
        ->call('toggle')
        ->assertHasNoErrors();

    $person->refresh();

    expect($person->is_publicly_visible)->toBeTrue()
        ->and(PersonPrivacy::isPubliclyVisible($person))->toBeTrue();

    $fields = PersonPrivacy::publicFields($person);

    // MVP opt-in is coarse-grained (spec.md Assumptions): the single
    // is_publicly_visible flag reveals the whole field set at once, per
    // data-model.md's single isPubliclyVisible() formula.
    expect($fields['firstname'])->toBe($person->firstname)
        ->and($fields['street'])->toBe('Secret Street');
});
