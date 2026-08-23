<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use App\Support\PersonPrivacy;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('clearing a death date preserves a prior opt-in choice', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'yod'                 => null,
        'dod'                 => '2020-01-01',
        'is_publicly_visible' => true,
    ]);

    $this->actingAs($user);

    Livewire\Livewire::test('people::edit.death', ['person' => $person])
        ->set('dod', null)
        ->set('yod', null)
        ->call('saveDeath')
        ->assertHasNoErrors();

    $person->refresh();

    expect($person->is_publicly_visible)->toBeTrue()
        ->and(PersonPrivacy::isPubliclyVisible($person))->toBeTrue();
});
