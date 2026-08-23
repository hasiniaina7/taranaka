<?php

declare(strict_types=1);

use App\Models\Person;
use App\Support\PersonPrivacy;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('isLiving and isPubliclyVisible reflect whether a death date is recorded', function (): void {
    $deceased = Person::factory()->make(['yod' => 2000, 'dod' => null]);
    $living   = Person::factory()->make(['yod' => null, 'dod' => null]);

    expect(PersonPrivacy::isLiving($deceased))->toBeFalse();
    expect(PersonPrivacy::isPubliclyVisible($deceased))->toBeTrue();

    expect(PersonPrivacy::isLiving($living))->toBeTrue();
    expect(PersonPrivacy::isPubliclyVisible($living))->toBeFalse();
});

test('publicFields omits sensitive fields for a living, non-opted-in person', function (): void {
    $living = Person::factory()->make([
        'yod'         => null,
        'dod'         => null,
        'dob'         => '1990-01-01',
        'street'      => 'Main Street',
        'number'      => '12',
        'postal_code' => '75000',
        'city'        => 'Paris',
        'province'    => 'Ile-de-France',
        'state'       => 'IDF',
        'country'     => 'France',
        'phone'       => '0102030405',
    ]);

    $fields = PersonPrivacy::publicFields($living);

    foreach (['street', 'number', 'postal_code', 'city', 'province', 'state', 'country', 'phone', 'dob'] as $sensitive) {
        expect($fields[$sensitive])->toBeNull();
    }

    foreach (['firstname', 'surname', 'birthname', 'nickname', 'photo', 'summary'] as $always) {
        expect($fields)->toHaveKey($always);
    }

    foreach (['lineages', 'father', 'mother', 'partners', 'children'] as $relation) {
        expect($fields)->toHaveKey($relation);
    }
});

test('isPubliclyVisible reflects the living/opted-in/deceased-via-dod/deceased-via-yod matrix', function (): void {
    $livingNotOptedIn = Person::factory()->make(['yod' => null, 'dod' => null, 'is_publicly_visible' => false]);
    $livingOptedIn    = Person::factory()->make(['yod' => null, 'dod' => null, 'is_publicly_visible' => true]);
    $deceasedViaDod   = Person::factory()->make(['yod' => null, 'dod' => '2000-01-01', 'is_publicly_visible' => false]);
    $deceasedViaYod   = Person::factory()->make(['yod' => 2000, 'dod' => null, 'is_publicly_visible' => false]);

    expect(PersonPrivacy::isPubliclyVisible($livingNotOptedIn))->toBeFalse()
        ->and(PersonPrivacy::isPubliclyVisible($livingOptedIn))->toBeTrue()
        ->and(PersonPrivacy::isPubliclyVisible($deceasedViaDod))->toBeTrue()
        ->and(PersonPrivacy::isPubliclyVisible($deceasedViaYod))->toBeTrue();
});

test('publicFields includes sensitive fields for a deceased person', function (): void {
    $deceased = Person::factory()->make([
        'yod'    => 2000,
        'dob'    => '1930-05-01',
        'street' => 'Main Street',
        'phone'  => '0102030405',
    ]);

    $fields = PersonPrivacy::publicFields($deceased);

    expect($fields['dob']?->format('Y-m-d'))->toBe('1930-05-01');
    expect($fields['street'])->toBe('Main Street');
    expect($fields['phone'])->toBe('0102030405');
});
