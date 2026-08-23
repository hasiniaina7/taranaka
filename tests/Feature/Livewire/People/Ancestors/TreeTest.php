<?php

declare(strict_types=1);

use App\Livewire\People\Ancestors\Tree;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('branches expand progressively and can collapse without discarding loaded ancestors', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $grandfather = Person::factory()->withUser($user)->create([
        'firstname' => 'Paternal Grandfather',
        'yod'       => 1980,
    ]);
    $father = Person::factory()->withUser($user)->create([
        'firstname' => 'Known Father',
        'father_id' => $grandfather->id,
        'yod'       => 2000,
    ]);
    $person = Person::factory()->withUser($user)->create([
        'firstname' => 'Root Person',
        'father_id' => $father->id,
        'yod'       => 2020,
    ]);

    Livewire::test(Tree::class, ['personId' => $person->id, 'maxDepth' => 3])
        ->assertSee('Known Father')
        ->assertDontSee('Paternal Grandfather')
        ->call('toggleBranch', $father->id)
        ->assertSee('Paternal Grandfather')
        ->assertSet('expandedNodeIds', fn (array $ids): bool => in_array($father->id, $ids, true))
        ->call('toggleBranch', $father->id)
        ->assertDontSee('Paternal Grandfather')
        ->assertSet('loadedNodeIds', fn (array $ids): bool => in_array($father->id, $ids, true))
        ->call('toggleBranch', $father->id)
        ->assertSee('Paternal Grandfather');
});

test('the generation limit prevents loading a deeper branch', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $grandfather = Person::factory()->withUser($user)->create([
        'firstname' => 'Hidden Grandfather',
        'yod'       => 1980,
    ]);
    $father = Person::factory()->withUser($user)->create([
        'firstname' => 'Visible Father',
        'father_id' => $grandfather->id,
        'yod'       => 2000,
    ]);
    $person = Person::factory()->withUser($user)->create([
        'father_id' => $father->id,
        'yod'       => 2020,
    ]);

    Livewire::test(Tree::class, ['personId' => $person->id, 'maxDepth' => 1])
        ->assertSee('Visible Father')
        ->assertSee('Limite de générations atteinte.')
        ->call('toggleBranch', $father->id)
        ->assertDontSee('Hidden Grandfather');
});

test('a living ancestor remains visible without exposing an exact birth date', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $livingFather = Person::factory()->withUser($user)->create([
        'firstname' => 'Living Father',
        'dob'       => '1975-04-12',
        'yob'       => 1975,
        'dod'       => null,
        'yod'       => null,
    ]);
    $person = Person::factory()->withUser($user)->create([
        'father_id' => $livingFather->id,
        'yod'       => 2020,
    ]);

    Livewire::test(Tree::class, ['personId' => $person->id, 'maxDepth' => 2])
        ->assertSee('Living Father')
        ->assertSee('1975')
        ->assertDontSee('1975-04-12')
        ->assertSet('nodes', fn (array $nodes): bool => collect($nodes)->every(
            fn (array $node): bool => ! array_key_exists('dob', $node) && ! array_key_exists('dod', $node),
        ));
});

test('a partial parent generation renders an unknown slot without creating a person', function (): void {
    $user = User::factory()->withPersonalTeam()->create();

    $mother = Person::factory()->withUser($user)->create([
        'firstname' => 'Known Mother',
        'yod'       => 2000,
    ]);
    $person = Person::factory()->withUser($user)->create([
        'firstname' => 'Root Person',
        'mother_id' => $mother->id,
        'yod'       => 2020,
    ]);
    $peopleCount = Person::withoutGlobalScopes()->count();

    Livewire::test(Tree::class, ['personId' => $person->id, 'maxDepth' => 3])
        ->assertSee('Known Mother')
        ->assertSee('Parent inconnu');

    expect(Person::withoutGlobalScopes()->count())->toBe($peopleCount);
});

test('a person without ancestors renders the root and an explicit empty state', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'firstname' => 'Only Root',
        'yod'       => 2020,
    ]);

    Livewire::test(Tree::class, ['personId' => $person->id, 'maxDepth' => 3])
        ->assertSee('Only Root')
        ->assertSee('Aucun ancêtre enregistré.')
        ->assertDontSee('Parent inconnu');
});
