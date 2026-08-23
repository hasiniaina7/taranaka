<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('accepting a pending proposal applies the change and records moderator and timestamp', function (): void {
    $owner     = User::factory()->withPersonalTeam()->create();
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);
    $person    = Person::factory()->withUser($owner)->create(['surname' => 'Original']);

    $contribution = Contribution::factory()->pending()->create([
        'target_type' => Contribution::TARGET_PERSON,
        'target_id'   => $person->id,
        'field'       => 'surname',
        'old_value'   => 'Original',
        'new_value'   => 'Corrected',
    ]);

    $this->actingAs($moderator);

    Livewire\Livewire::test('contributions::review', ['contribution' => $contribution])
        ->call('accept')
        ->assertHasNoErrors();

    $contribution->refresh();

    expect($contribution->status)->toBe(Contribution::STATUS_ACCEPTED)
        ->and($contribution->reviewer_id)->toBe($moderator->id)
        ->and($contribution->reviewed_at)->not->toBeNull();

    expect(Person::withoutGlobalScope('team')->find($person->id)->surname)->toBe('Corrected');
});
