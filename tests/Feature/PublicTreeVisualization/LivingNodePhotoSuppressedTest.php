<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Livewire\People\Ancestors\Tree as AncestorsTree;
use App\Livewire\People\Descendants\Tree as DescendantsTree;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FakeDescendantsQuery;

test('a living, non-opted-in person never exposes a photo url in the descendant tree', function (): void {
    Storage::fake('photos');

    $user = User::factory()->withPersonalTeam()->create();
    $root = Person::factory()->withUser($user)->create([
        'photo'               => 'living',
        'dod'                 => null,
        'yod'                 => null,
        'is_publicly_visible' => false,
    ]);

    Storage::disk('photos')->put("{$root->team_id}/{$root->id}/living_small.webp", 'fake-image-bytes');

    app()->instance(DescendantsQueryInterface::class, new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
    ])));

    $tree = Livewire::test(DescendantsTree::class, ['person' => $root, 'maxDepth' => 3])
        ->instance()
        ->tree();

    expect($tree['photo_url'])->toBeNull();
});

test('a living, non-opted-in ancestor never exposes a photo url in the ancestor tree', function (): void {
    Storage::fake('photos');

    $user   = User::factory()->withPersonalTeam()->create();
    $father = Person::factory()->withUser($user)->create([
        'photo'               => 'living',
        'dod'                 => null,
        'yod'                 => null,
        'is_publicly_visible' => false,
    ]);
    $person = Person::factory()->withUser($user)->create([
        'father_id' => $father->id,
        'yod'       => 2020,
    ]);

    Storage::disk('photos')->put("{$father->team_id}/{$father->id}/living_small.webp", 'fake-image-bytes');

    $tree = Livewire::test(AncestorsTree::class, ['personId' => $person->id, 'maxDepth' => 3])
        ->instance()
        ->tree();

    $fatherNode = collect($tree['children'])->firstWhere('id', $father->id);

    expect($fatherNode['photo_url'])->toBeNull();
});
