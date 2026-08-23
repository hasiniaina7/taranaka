<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;

/**
 * Single, reusable public-visibility rule for a Person (spec 007), consumed
 * by every public surface (specs 003/004/005/006) instead of each feature
 * reimplementing its own living/private check.
 */
class PersonPrivacy
{
    public static function isLiving(Person $person): bool
    {
        return ! $person->isDeceased();
    }

    public static function isPubliclyVisible(Person $person): bool
    {
        return ! self::isLiving($person) || (bool) $person->is_publicly_visible;
    }

    /**
     * Apply the public visibility rule before pagination so hidden lineage members
     * cannot affect response size or force an unbounded in-memory filter.
     *
     * @param  Builder<Person>  $query
     * @return Builder<Person>
     */
    public static function publiclyVisibleQuery(Builder $query): Builder
    {
        return $query->where(function (Builder $visibilityQuery): void {
            $visibilityQuery
                ->whereNotNull('dod')
                ->orWhereNotNull('yod')
                ->orWhere('is_publicly_visible', true);
        });
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
