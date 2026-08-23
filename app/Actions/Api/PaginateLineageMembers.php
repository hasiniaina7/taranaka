<?php

declare(strict_types=1);

namespace App\Actions\Api;

use App\Models\Lineage;
use App\Models\Person;
use App\Support\PersonPrivacy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Paginate only publicly visible members of a lineage across team ownership boundaries.
 *
 * The query is extracted so controllers never duplicate privacy predicates. Callers receive a
 * database-bounded paginator whose Person models are safe for the compact PersonResource shape.
 */
class PaginateLineageMembers
{
    /** @return LengthAwarePaginator<int, Person> */
    public function execute(Lineage $lineage, int $perPage): LengthAwarePaginator
    {
        $query = Person::withoutGlobalScope('team')
            ->with('lineages:id,name,slug')
            ->whereHas('lineages', fn ($lineageQuery) => $lineageQuery->whereKey($lineage->id))
            ->orderBy('surname')
            ->orderBy('firstname')
            ->orderBy('people.id');

        $paginator = PersonPrivacy::publiclyVisibleQuery($query)->paginate($perPage);

        $paginator->getCollection()->each(function (Person $person): void {
            $person->setRelation('father', null);
            $person->setRelation('mother', null);
            $person->setRelation('partners', collect());
            $person->setRelation('children', collect());
        });

        return $paginator;
    }
}
