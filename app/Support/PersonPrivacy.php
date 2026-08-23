<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Person;

/**
 * Interim public-visibility rule for a Person (spec 003).
 *
 * Spec 007 replaces the body of {@see isPubliclyVisible()} to also check an
 * opt-in flag, without changing this class's call sites.
 */
final class PersonPrivacy
{
    public static function isLiving(Person $person): bool
    {
        return ! $person->isDeceased();
    }

    public static function isPubliclyVisible(Person $person): bool
    {
        return ! self::isLiving($person);
    }

    /**
     * Privacy-filtered projection of a person's data for public display
     * (spec 003 data-model.md).
     *
     * @return array<string, mixed>
     */
    public static function publicFields(Person $person): array
    {
        $visible = self::isPubliclyVisible($person);

        return [
            'firstname'   => $person->firstname,
            'surname'     => $person->surname,
            'birthname'   => $person->birthname,
            'nickname'    => $person->nickname,
            'photo'       => $person->photo,
            'summary'     => $person->summary,
            'yob'         => $person->yob,
            'yod'         => $person->yod,
            'dob'         => $visible ? $person->dob : null,
            'dod'         => $visible ? $person->dod : null,
            'street'      => $visible ? $person->street : null,
            'number'      => $visible ? $person->number : null,
            'postal_code' => $visible ? $person->postal_code : null,
            'city'        => $visible ? $person->city : null,
            'province'    => $visible ? $person->province : null,
            'state'       => $visible ? $person->state : null,
            'country'     => $visible ? $person->country : null,
            'phone'       => $visible ? $person->phone : null,
            'lineages'    => $person->lineages,
            'father'      => $person->father,
            'mother'      => $person->mother,
            'partners'    => $person->partners,
            'children'    => $person->children,
        ];
    }
}
