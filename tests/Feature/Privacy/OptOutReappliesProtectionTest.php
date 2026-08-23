<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use App\Support\PersonPrivacy;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('reversing the opt-in immediately re-applies default protection', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'yod'                 => null,
        'dod'                 => null,
        'is_publicly_visible' => true,
    ]);

    $this->actingAs($user);

    expect(PersonPrivacy::isPubliclyVisible($person))->toBeTrue();

    Livewire\Livewire::test('people::edit.privacy', ['person' => $person])
        ->call('toggle')
        ->assertHasNoErrors();

    $person->refresh();

    expect($person->is_publicly_visible)->toBeFalse()
        ->and(PersonPrivacy::isPubliclyVisible($person))->toBeFalse();
});
