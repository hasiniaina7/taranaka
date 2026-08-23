<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a user sees their own pending contribution with a pending badge', function (): void {
    $author = User::factory()->withPersonalTeam()->create();

    Contribution::factory()->pending()->create(['author_id' => $author->id]);

    $this->actingAs($author);

    Livewire\Livewire::test('contributions::my')
        ->assertSee(__('contributions.status_pending'));
});
