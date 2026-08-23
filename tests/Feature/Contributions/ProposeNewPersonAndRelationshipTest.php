<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('proposing a new child queues the proposal without creating the person yet', function (): void {
    $owner  = User::factory()->withPersonalTeam()->create();
    $author = User::factory()->withPersonalTeam()->create();
    $parent = Person::factory()->withUser($owner)->create(['sex' => 'm']);

    $this->actingAs($author);

    Livewire\Livewire::test('contributions::propose-child', ['person' => $parent])
        ->set('firstname', 'New')
        ->set('surname', 'Child')
        ->set('sex', 'f')
        ->call('submit')
        ->assertHasNoErrors();

    $contribution = Contribution::first();

    expect($contribution)->not->toBeNull()
        ->and($contribution->target_type)->toBe(Contribution::TARGET_PERSON_NEW)
        ->and($contribution->target_id)->toBeNull()
        ->and($contribution->status)->toBe(Contribution::STATUS_PENDING);

    expect(Person::withoutGlobalScope('team')->where('firstname', 'New')->where('surname', 'Child')->exists())->toBeFalse();
});

test('accepting a new-child proposal creates the person and links it as a child', function (): void {
    $owner     = User::factory()->withPersonalTeam()->create();
    $author    = User::factory()->withPersonalTeam()->create();
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);
    $parent    = Person::factory()->withUser($owner)->create(['sex' => 'm']);

    $this->actingAs($author);

    Livewire\Livewire::test('contributions::propose-child', ['person' => $parent])
        ->set('firstname', 'New')
        ->set('surname', 'Child')
        ->set('sex', 'f')
        ->call('submit');

    $contribution = Contribution::first();

    $this->actingAs($moderator);

    Livewire\Livewire::test('contributions::review', ['contribution' => $contribution])
        ->call('accept')
        ->assertHasNoErrors();

    $contribution->refresh();

    expect($contribution->status)->toBe(Contribution::STATUS_ACCEPTED)
        ->and($contribution->target_id)->not->toBeNull();

    $child = Person::withoutGlobalScope('team')->find($contribution->target_id);

    expect($child)->not->toBeNull()
        ->and($child->firstname)->toBe('New')
        ->and($child->surname)->toBe('Child')
        ->and($child->father_id)->toBe($parent->id);
});
