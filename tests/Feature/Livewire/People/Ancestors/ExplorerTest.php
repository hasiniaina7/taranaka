<?php

declare(strict_types=1);

use App\Livewire\People\Ancestors\Explorer;
use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('the explorer switches between tree and list views and bounds its generation limit', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create([
        'firstname' => 'Explorer Root',
        'yod'       => 2020,
    ]);

    Livewire::test(Explorer::class, ['personId' => $person->id])
        ->assertSee('Explorer Root')
        ->assertSet('view', 'tree')
        ->call('showList')
        ->assertSet('view', 'list')
        ->call('showTree')
        ->assertSet('view', 'tree')
        ->set('maxDepth', 0)
        ->assertSet('maxDepth', 1)
        ->set('maxDepth', 200)
        ->assertSet('maxDepth', 128);
});
