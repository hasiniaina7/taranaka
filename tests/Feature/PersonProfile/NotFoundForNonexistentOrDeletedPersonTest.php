<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('a nonexistent person profile returns a styled 404', function (): void {
    $response = $this->get('/p/999999');

    $response->assertNotFound();
});

test('a soft-deleted person profile returns a styled 404', function (): void {
    $user   = User::factory()->withPersonalTeam()->create();
    $person = Person::factory()->withUser($user)->create();
    $person->delete();

    $response = $this->get("/p/{$person->id}");

    $response->assertNotFound();
});
