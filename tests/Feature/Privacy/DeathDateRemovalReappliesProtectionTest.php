<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use App\Support\PersonPrivacy;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('clearing a death date reverts to default protection and never flips the stored opt-in', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'yod'                 => null,
        'dod'                 => '2020-01-01',
        'is_publicly_visible' => false,
    ]);

    $this->actingAs($user);

    expect(PersonPrivacy::isPubliclyVisible($person))->toBeTrue();

    Livewire\Livewire::test('people::edit.death', ['person' => $person])
        ->set('dod', null)
        ->set('yod', null)
        ->call('saveDeath')
        ->assertHasNoErrors();

    $person->refresh();

    expect(PersonPrivacy::isPubliclyVisible($person))->toBeFalse()
        ->and($person->is_publicly_visible)->toBeFalse();
});
