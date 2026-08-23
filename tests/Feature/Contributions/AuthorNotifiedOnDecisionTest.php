<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('the author sees the decision on their own contribution without opening it', function (): void {
    $author = User::factory()->withPersonalTeam()->create();

    Contribution::factory()->accepted()->create(['author_id' => $author->id]);
    Contribution::factory()->rejected()->create(['author_id' => $author->id, 'rejection_reason' => 'Not enough evidence']);

    $this->actingAs($author);

    Livewire\Livewire::test('contributions::my')
        ->assertSee(__('contributions.status_accepted'))
        ->assertSee(__('contributions.status_rejected'))
        ->assertSee('Not enough evidence');
});
