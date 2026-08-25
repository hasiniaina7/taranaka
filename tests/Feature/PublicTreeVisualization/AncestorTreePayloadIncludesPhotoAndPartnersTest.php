<?php

declare(strict_types=1);

use App\Livewire\People\Ancestors\Tree;
use App\Models\Couple;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('the ancestor tree payload includes a photo url and partner ids', function (): void {
    Storage::fake('photos');

    $user   = User::factory()->withPersonalTeam()->create();
    $father = Person::factory()->withUser($user)->create(['yod' => 2000, 'photo' => 'father']);
    $person = Person::factory()->withUser($user)->create([
        'father_id' => $father->id,
        'yod'       => 2020,
    ]);
    $partner = Person::factory()->withUser($user)->create(['yod' => 1999]);
    Couple::factory()->create([
        'person1_id' => $father->id,
        'person2_id' => $partner->id,
        'team_id'    => $user->currentTeam->id,
    ]);

    Storage::disk('photos')->put("{$father->team_id}/{$father->id}/father_small.webp", 'fake-image-bytes');

    $tree = Livewire::test(Tree::class, ['personId' => $person->id, 'maxDepth' => 3])
        ->instance()
        ->tree();

    $fatherNode = collect($tree['children'])->firstWhere('id', $father->id);

    expect($fatherNode['photo_url'])->toBe(Storage::disk('photos')->url("{$father->team_id}/{$father->id}/father_small.webp"));
    expect($fatherNode['partners'])->toBe([[
        'id'        => $partner->id,
        'name'      => $partner->name,
        'photo_url' => null,
    ]]);
    expect($tree['photo_url'])->toBeNull();
    expect($tree['partners'])->toBe([]);
});
