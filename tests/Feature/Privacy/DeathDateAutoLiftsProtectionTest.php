<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use App\Support\PersonPrivacy;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('recording a death date auto-lifts protection without a separate publish step', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'dob'                 => '1980-01-01',
        'yob'                 => 1980,
        'yod'                 => null,
        'dod'                 => null,
        'is_publicly_visible' => false,
    ]);

    $this->actingAs($user);

    expect(PersonPrivacy::isPubliclyVisible($person))->toBeFalse();

    Livewire\Livewire::test('people::edit.death', ['person' => $person])
        ->set('dod', '2020-01-01')
        ->call('saveDeath')
        ->assertHasNoErrors();

    $person->refresh();

    expect(PersonPrivacy::isPubliclyVisible($person))->toBeTrue()
        ->and($person->is_publicly_visible)->toBeFalse();
});
