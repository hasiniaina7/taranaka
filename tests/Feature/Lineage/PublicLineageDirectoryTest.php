<?php

declare(strict_types=1);

use App\Models\Lineage;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a visitor can view the public lineage directory', function (): void {
    Lineage::factory()->create(['name' => 'RAKOTO']);
    Lineage::factory()->create(['name' => 'RABE']);

    $this->get(route('lineages.index'))
        ->assertOk()
        ->assertSee('RAKOTO')
        ->assertSee('RABE');
});

test('the lineage directory is filterable by name', function (): void {
    Lineage::factory()->create(['name' => 'RAKOTO']);
    Lineage::factory()->create(['name' => 'RABE']);

    Livewire::test('lineages::directory')
        ->set('search', 'RAKOTO')
        ->assertSee('RAKOTO')
        ->assertDontSee('RABE');
});
