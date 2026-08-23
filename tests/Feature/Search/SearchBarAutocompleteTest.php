<?php

declare(strict_types=1);

use App\Livewire\SearchBar;
use App\Models\Lineage;
use App\Models\Person;
use Livewire\Livewire;

test('autocomplete waits for the minimum query length', function (): void {
    Person::factory()->create([
        'firstname' => 'Alphonse',
        'surname'   => 'Needle',
        'yod'       => 2000,
    ]);

    Livewire::test(SearchBar::class)
        ->set('query', 'A')
        ->assertSee('Continuez à saisir pour lancer la recherche.')
        ->assertDontSee('Alphonse Needle');
});

test('autocomplete groups person and lineage matches and protects living details', function (): void {
    $lineage = Lineage::factory()->create(['name' => 'Autocomplete Family']);
    $person  = Person::factory()->create([
        'firstname' => 'Autocomplete',
        'surname'   => 'Living',
        'dob'       => '1990-01-02',
        'dod'       => null,
        'yod'       => null,
        'phone'     => '555-AUTOCOMPLETE',
    ]);

    $person->lineages()->attach($lineage);

    Livewire::test(SearchBar::class)
        ->set('query', 'Autocomplete')
        ->assertSeeInOrder(['Personnes', 'Autocomplete Living', 'Autocomplete Family'])
        ->assertSee('global-search-lineages-heading')
        ->assertSee('Profil privé')
        ->assertDontSee('1990-01-02')
        ->assertDontSee('555-AUTOCOMPLETE');
});

test('submitting autocomplete redirects to the canonical public search route', function (): void {
    Livewire::test(SearchBar::class)
        ->set('query', '  Jean <b>Rakoto</b>  ')
        ->call('submit')
        ->assertRedirect(route('public.search', ['q' => 'Jean Rakoto']));
});
