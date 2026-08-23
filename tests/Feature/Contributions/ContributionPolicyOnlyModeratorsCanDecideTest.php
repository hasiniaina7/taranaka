<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a non-moderator cannot decide on a contribution', function (): void {
    $author       = User::factory()->withPersonalTeam()->create();
    $nonModerator = User::factory()->withPersonalTeam()->create(['is_moderator' => false]);

    $contribution = Contribution::factory()->pending()->create(['author_id' => $author->id]);

    expect($nonModerator->can('accept', $contribution))->toBeFalse()
        ->and($nonModerator->can('reject', $contribution))->toBeFalse();
});

test('a moderator cannot decide on their own contribution (self-review guard)', function (): void {
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);

    $contribution = Contribution::factory()->pending()->create(['author_id' => $moderator->id]);

    expect($moderator->can('accept', $contribution))->toBeFalse()
        ->and($moderator->can('reject', $contribution))->toBeFalse();
});

test('a moderator can decide on a contribution authored by someone else', function (): void {
    $author    = User::factory()->withPersonalTeam()->create();
    $moderator = User::factory()->withPersonalTeam()->create(['is_moderator' => true]);

    $contribution = Contribution::factory()->pending()->create(['author_id' => $author->id]);

    expect($moderator->can('accept', $contribution))->toBeTrue()
        ->and($moderator->can('reject', $contribution))->toBeTrue();
});
