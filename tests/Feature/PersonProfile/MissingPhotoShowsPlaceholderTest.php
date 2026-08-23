<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a person with no photo renders a placeholder, never a broken image', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create(['photo' => null, 'yod' => 2000]);

    $response = $this->get("/p/{$person->id}");

    $response->assertOk();
    $response->assertSee('data-person-photo-placeholder', false);
    $response->assertDontSee('<img', false);
});
