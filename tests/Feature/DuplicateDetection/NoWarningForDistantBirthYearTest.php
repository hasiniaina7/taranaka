<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('a matching name at least twenty birth years away does not warn or gate saving', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1954,
        'dob'       => null,
    ]);

    $component = Livewire::actingAs($user)
        ->test('people::add.person')
        ->set('form.firstname', 'Jean')
        ->set('form.surname', 'Rakoto')
        ->set('form.yob', '1974');

    expect($component->get('duplicateCandidates'))->toBe([])
        ->and($component->get('requiresDuplicateAcknowledgment'))->toBeFalse();
});

test('a name-only match is a low-confidence hint and does not gate saving', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => null,
        'dob'       => null,
    ]);

    $component = Livewire::actingAs($user)
        ->test('people::add.person')
        ->set('form.firstname', 'Jean')
        ->set('form.surname', 'Rakoto');

    expect($component->get('duplicateCandidates'))->toHaveCount(1)
        ->and($component->get('duplicateCandidates')[0]['score'])->toBe(0.65)
        ->and($component->get('requiresDuplicateAcknowledgment'))->toBeFalse();
});

test('wildcard characters are treated as name text rather than broad query operators', function (): void {
    $user = User::factory()->withPersonalTeam()->create();
    Person::factory()->withUser($user)->create([
        'firstname' => 'Completely',
        'surname'   => 'Different',
        'yob'       => 1954,
        'dob'       => null,
    ]);

    $component = Livewire::actingAs($user)
        ->test('people::add.person')
        ->set('form.surname', '%_%')
        ->set('form.yob', '1954');

    expect($component->get('duplicateCandidates'))->toBe([]);
});
