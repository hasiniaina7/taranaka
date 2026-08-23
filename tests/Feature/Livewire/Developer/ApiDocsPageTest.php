<?php

declare(strict_types=1);

use App\Models\User;

test('a developer can browse every version one API contract', function (): void {
    $developer = User::factory()->withPersonalTeam()->create(['is_developer' => true]);

    test()->actingAs($developer);

    test()->get(route('developer.api-docs'))
        ->assertOk()
        ->assertSee('API v1')
        ->assertSee('/api/v1/persons/{person}')
        ->assertSee('/api/v1/persons/{person}/descendants')
        ->assertSee('/api/v1/persons/{person}/ancestors')
        ->assertSee('/api/v1/lineages/{lineage}')
        ->assertSee('/api/v1/lineages/{lineage}/members')
        ->assertSee('/api/v1/search')
        ->assertSee('&quot;is_private&quot;: true', false)
        ->assertDontSee('&quot;phone&quot;', false);
});

test('the API docs remain behind the existing developer gate', function (): void {
    $user = User::factory()->withPersonalTeam()->create(['is_developer' => false]);

    test()->actingAs($user);

    test()->get(route('developer.api-docs'))->assertForbidden();
});

test('guests are redirected before reaching the developer gate', function (): void {
    test()->get(route('developer.api-docs'))->assertRedirect(route('login'));
});
