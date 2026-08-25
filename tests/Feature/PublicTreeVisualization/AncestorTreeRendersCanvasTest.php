<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

test('the public ancestor tree page renders the family-tree canvas with the loaded branch payload', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $father = Person::factory()->withUser($user)->create(['firstname' => 'Canvas', 'surname' => 'Father', 'yod' => 2000]);
    $person = Person::factory()->withUser($user)->create([
        'firstname' => 'Canvas',
        'surname'   => 'Root',
        'father_id' => $father->id,
        'yod'       => 2020,
    ]);

    TestResponse::fromBaseResponse(app(Kernel::class)->handle(Request::create(route('front.people.ancestors', $person))))
        ->assertOk()
        ->assertSee('family-tree-canvas', false)
        ->assertSee('familyTreeCanvas', false)
        ->assertSee('Canvas Root')
        ->assertSee('Canvas Father');
});
