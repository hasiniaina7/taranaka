<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('creating a lineage with a case-insensitive duplicate name warns but still saves', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    Lineage::factory()->create(['name' => 'RAKOTO', 'slug' => 'rakoto']);

    Livewire::actingAs($user)
        ->test('lineages::form')
        ->set('name', 'rakoto')
        ->assertSet('duplicateNameWarning', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('lineages', ['name' => 'rakoto', 'slug' => 'rakoto-2']);
});
