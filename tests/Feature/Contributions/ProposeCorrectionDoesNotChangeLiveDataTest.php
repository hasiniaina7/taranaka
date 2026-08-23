<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('submitting a correction proposal records it without changing the target person live data', function (): void {
    $owner  = User::factory()->withPersonalTeam()->create();
    $author = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($owner)->create(['surname' => 'Original']);

    $this->actingAs($author);

    Livewire\Livewire::test('contributions::propose', ['person' => $person])
        ->set('field', 'surname')
        ->set('new_value', 'Corrected')
        ->set('justification', 'Typo in the original record')
        ->call('submit')
        ->assertHasNoErrors();

    $contribution = Contribution::first();

    expect($contribution)->not->toBeNull()
        ->and($contribution->author_id)->toBe($author->id)
        ->and($contribution->field)->toBe('surname')
        ->and($contribution->old_value)->toBe('Original')
        ->and($contribution->new_value)->toBe('Corrected')
        ->and($contribution->status)->toBe(Contribution::STATUS_PENDING);

    expect(Person::withoutGlobalScope('team')->find($person->id)->surname)->toBe('Original');
});
