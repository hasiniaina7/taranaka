<?php

declare(strict_types=1);

use App\Livewire\People\Ancestors\ListView;
use App\Models\Lineage;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('the ancestor list shows generation lineage and birth year', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $grandfather = Person::factory()->withUser($user)->create([
        'firstname' => 'Listed Grandfather',
        'yob'       => 1940,
        'yod'       => 2000,
    ]);
    $father = Person::factory()->withUser($user)->create([
        'firstname' => 'Listed Father',
        'father_id' => $grandfather->id,
        'yob'       => 1970,
        'yod'       => 2020,
    ]);
    $person = Person::factory()->withUser($user)->create([
        'father_id' => $father->id,
        'yod'       => 2021,
    ]);
    $lineage = Lineage::factory()->create(['name' => 'Explorer Lineage']);
    $lineage->people()->attach($father);

    Livewire::test(ListView::class, ['personId' => $person->id, 'maxDepth' => 3])
        ->assertSee('Listed Father')
        ->assertSee('Listed Grandfather')
        ->assertSee('Explorer Lineage')
        ->assertSee('1970')
        ->assertSee('1940')
        ->assertSee(route('public.people.show', $father), false);
});
test('the ancestor list filters by generation', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $grandfather = Person::factory()->withUser($user)->create([
        'firstname' => 'Generation Two',
        'yod'       => 2000,
    ]);
    $father = Person::factory()->withUser($user)->create([
        'firstname' => 'Generation One',
        'father_id' => $grandfather->id,
        'yod'       => 2020,
    ]);
    $person = Person::factory()->withUser($user)->create([
        'father_id' => $father->id,
        'yod'       => 2021,
    ]);

    Livewire::test(ListView::class, ['personId' => $person->id, 'maxDepth' => 3])
        ->set('generationFilter', 2)
        ->assertSee('Generation Two')
        ->assertDontSee('Generation One')
        ->set('generationFilter', null)
        ->assertSee('Generation Two')
        ->assertSee('Generation One');
});
