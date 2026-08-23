<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;

test('public search returns people across teams for guests and authenticated contributors', function (): void {
    $searcher = User::factory()->withPersonalTeam()->create();
    $owner    = User::factory()->withPersonalTeam()->create();

    $person = Person::factory()->withUser($owner)->create([
        'firstname' => 'Interteam',
        'surname'   => 'Discovery',
        'yob'       => 1940,
        'yod'       => 2010,
    ]);

    test()->get(route('public.search', ['q' => 'Interteam']))
        ->assertOk()
        ->assertSee($person->name);

    test()->actingAs($searcher);

    test()->get(route('public.search', ['q' => 'Interteam']))
        ->assertOk()
        ->assertSee($person->name);
});

test('authentication does not reveal private details of a living person owned by another team', function (): void {
    $searcher = User::factory()->withPersonalTeam()->create();
    $owner    = User::factory()->withPersonalTeam()->create();

    $person = Person::factory()->withUser($owner)->create([
        'firstname' => 'Interteamliving',
        'surname'   => 'Privacy',
        'dob'       => '1991-04-17',
        'yod'       => null,
        'dod'       => null,
        'street'    => 'Hidden Cross-Team Street',
        'phone'     => '555-CROSS-TEAM',
    ]);

    $guestResponse = test()->get(route('public.search', ['q' => 'Interteamliving']))
        ->assertOk()
        ->assertSee($person->name)
        ->assertSee('Profil privé')
        ->assertDontSee('1991-04-17')
        ->assertDontSee('Hidden Cross-Team Street')
        ->assertDontSee('555-CROSS-TEAM');

    test()->actingAs($searcher);

    $authenticatedResponse = test()->get(route('public.search', ['q' => 'Interteamliving']))
        ->assertOk()
        ->assertSee($person->name)
        ->assertSee('Profil privé')
        ->assertDontSee('1991-04-17')
        ->assertDontSee('Hidden Cross-Team Street')
        ->assertDontSee('555-CROSS-TEAM');

    expect($authenticatedResponse->getContent())->toContain('Profil privé');
    expect($guestResponse->getContent())->toContain('Profil privé');
});
