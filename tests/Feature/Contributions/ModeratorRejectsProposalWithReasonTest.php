<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('rejecting a proposal with a reason leaves the target unchanged and records the reason', function (): void {
    $owner     = User::factory()->withPersonalTeam()->create();
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);
    $person    = Person::factory()->withUser($owner)->create(['surname' => 'Original']);

    $contribution = Contribution::factory()->pending()->create([
        'target_type' => Contribution::TARGET_PERSON,
        'target_id'   => $person->id,
        'field'       => 'surname',
        'old_value'   => 'Original',
        'new_value'   => 'Wrong',
    ]);

    $this->actingAs($moderator);

    Livewire\Livewire::test('contributions::review', ['contribution' => $contribution])
        ->set('rejection_reason', 'Source could not be verified')
        ->call('reject')
        ->assertHasNoErrors();

    $contribution->refresh();

    expect($contribution->status)->toBe(Contribution::STATUS_REJECTED)
        ->and($contribution->reviewer_id)->toBe($moderator->id)
        ->and($contribution->rejection_reason)->toBe('Source could not be verified');

    expect(Person::withoutGlobalScope('team')->find($person->id)->surname)->toBe('Original');
});

test('rejecting without a reason is rejected by validation', function (): void {
    $owner     = User::factory()->withPersonalTeam()->create();
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);
    $person    = Person::factory()->withUser($owner)->create();

    $contribution = Contribution::factory()->pending()->create([
        'target_type' => Contribution::TARGET_PERSON,
        'target_id'   => $person->id,
        'field'       => 'surname',
    ]);

    $this->actingAs($moderator);

    Livewire\Livewire::test('contributions::review', ['contribution' => $contribution])
        ->set('rejection_reason', '')
        ->call('reject')
        ->assertHasErrors('rejection_reason');

    expect($contribution->fresh()->status)->toBe(Contribution::STATUS_PENDING);
});
