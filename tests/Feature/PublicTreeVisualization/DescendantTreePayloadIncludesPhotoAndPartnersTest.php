<?php

declare(strict_types=1);

use App\Contracts\DescendantsQueryInterface;
use App\Livewire\People\Descendants\Tree;
use App\Models\Couple;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FakeDescendantsQuery;

test('the descendant tree payload includes a photo url and partner ids', function (): void {
    Storage::fake('photos');

    $user  = User::factory()->withPersonalTeam()->create();
    $root  = Person::factory()->withUser($user)->create(['yod' => 1990, 'photo' => 'root']);
    $child = Person::factory()->withUser($user)->create([
        'firstname' => 'Deceased',
        'surname'   => 'Child',
        'father_id' => $root->id,
        'yod'       => 2010,
    ]);
    $partner = Person::factory()->withUser($user)->create(['yod' => 1995]);
    Couple::factory()->create([
        'person1_id' => $root->id,
        'person2_id' => $partner->id,
        'team_id'    => $user->currentTeam->id,
    ]);

    Storage::disk('photos')->put("{$root->team_id}/{$root->id}/root_small.webp", 'fake-image-bytes');

    app()->instance(DescendantsQueryInterface::class, new FakeDescendantsQuery(Collection::make([
        FakeDescendantsQuery::row($root, 0, (string) $root->id),
        FakeDescendantsQuery::row($child, 1, "{$root->id},{$child->id}"),
    ])));

    $tree = Livewire::test(Tree::class, ['person' => $root, 'maxDepth' => 3])
        ->instance()
        ->tree();

    expect($tree['photo_url'])->toBe(Storage::disk('photos')->url("{$root->team_id}/{$root->id}/root_small.webp"));
    expect($tree['partners'])->toBe([[
        'id'        => $partner->id,
        'name'      => $partner->name,
        'photo_url' => null,
    ]]);
    expect($tree['children'][0]['photo_url'])->toBeNull();
    expect($tree['children'][0]['partners'])->toBe([]);
});
