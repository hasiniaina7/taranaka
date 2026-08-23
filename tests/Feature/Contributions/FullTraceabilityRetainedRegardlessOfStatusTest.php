<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('an accepted contribution retains full traceability', function (): void {
    $owner     = User::factory()->withPersonalTeam()->create();
    $author    = User::factory()->withPersonalTeam()->create();
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);
    $person    = Person::factory()->withUser($owner)->create();

    $contribution = Contribution::factory()->pending()->create([
        'author_id'   => $author->id,
        'target_type' => Contribution::TARGET_PERSON,
        'target_id'   => $person->id,
        'field'       => 'surname',
        'old_value'   => 'Original',
        'new_value'   => 'Corrected',
    ]);

    $this->actingAs($moderator);

    Livewire\Livewire::test('contributions::review', ['contribution' => $contribution])->call('accept');

    $contribution->refresh();

    expect($contribution->author_id)->toBe($author->id)
        ->and($contribution->reviewer_id)->toBe($moderator->id)
        ->and($contribution->old_value)->toBe('Original')
        ->and($contribution->new_value)->toBe('Corrected')
        ->and($contribution->reviewed_at)->not->toBeNull()
        ->and($contribution->created_at)->not->toBeNull();
});

test('a rejected contribution retains full traceability including the reason', function (): void {
    $owner     = User::factory()->withPersonalTeam()->create();
    $author    = User::factory()->withPersonalTeam()->create();
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);
    $person    = Person::factory()->withUser($owner)->create();

    $contribution = Contribution::factory()->pending()->create([
        'author_id'   => $author->id,
        'target_type' => Contribution::TARGET_PERSON,
        'target_id'   => $person->id,
        'field'       => 'surname',
        'old_value'   => 'Original',
        'new_value'   => 'Wrong',
    ]);

    $this->actingAs($moderator);

    Livewire\Livewire::test('contributions::review', ['contribution' => $contribution])
        ->set('rejection_reason', 'Unverifiable')
        ->call('reject');

    $contribution->refresh();

    expect($contribution->author_id)->toBe($author->id)
        ->and($contribution->reviewer_id)->toBe($moderator->id)
        ->and($contribution->old_value)->toBe('Original')
        ->and($contribution->new_value)->toBe('Wrong')
        ->and($contribution->rejection_reason)->toBe('Unverifiable')
        ->and($contribution->reviewed_at)->not->toBeNull();
});
