<?php

declare(strict_types=1);

use App\Models\Person;
use App\Models\User;
use Livewire\Livewire;

test('birth year proximity controls which same-name candidate is warned about', function (): void {
    $user       = User::factory()->withPersonalTeam()->create();
    $closeMatch = Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1954,
        'dob'       => null,
    ]);
    $distantMatch = Person::factory()->withUser($user)->create([
        'firstname' => 'Jean',
        'surname'   => 'Rakoto',
        'yob'       => 1910,
        'dob'       => null,
    ]);

    $component = Livewire::actingAs($user)
        ->test('people::add.person')
        ->set('form.firstname', 'Jean')
        ->set('form.surname', 'Rakoto')
        ->set('form.yob', '1954');

    expect(collect($component->get('duplicateCandidates'))->pluck('id')->all())
        ->toBe([$closeMatch->id])
        ->not->toContain($distantMatch->id)
        ->and($component->get('duplicateCandidates')[0]['score'])->toBe(1.0);
});
