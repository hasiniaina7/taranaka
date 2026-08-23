<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a contributor can create a lineage', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    Livewire::actingAs($user)
        ->test('lineages::form')
        ->set('name', 'RAKOTO')
        ->set('description', 'Famille RAKOTO')
        ->set('origin', 'Antananarivo')
        ->call('save')
        ->assertHasNoErrors();

    $lineage = Lineage::query()->where('name', 'RAKOTO')->firstOrFail();

    expect($lineage->description)->toBe('Famille RAKOTO')
        ->and($lineage->origin)->toBe('Antananarivo')
        ->and($lineage->slug)->toBe('rakoto');

    $this->actingAs($user)->get(route('lineages.manage'))->assertSee('RAKOTO');
});
