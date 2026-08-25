<?php

declare(strict_types=1);

namespace App\Actions\Api;

use App\Models\Person;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Paginate an already-built genealogy traversal and hydrate only the requested page.
 *
 * Traversal stays in the established descendant Action and ancestor query engine. This Action only
 * preserves their order and degree while resolving cross-team Person models for API Resources.
 */
class PaginateTraversalPeople
{
    /**
     * @param  Collection<int, array{id:int, degree:int}>  $projection
     * @return LengthAwarePaginator<int, Person>
     */
    public function execute(Collection $projection, int $perPage, int $page): LengthAwarePaginator
    {
        $pageRows = $projection->forPage($page, $perPage)->values();
        $people   = Person::withoutGlobalScope('team')
            ->with('lineages:id,name,slug')
            ->whereKey($pageRows->pluck('id'))
            ->get()
            ->keyBy('id');

        $items = $pageRows
            ->map(function (array $row) use ($people): ?Person {
                $person = $people->get($row['id']);

                if (! $person instanceof Person) {
                    return null;
                }

                $person = clone $person;
                $person->setAttribute('degree', $row['degree']);
                $person->setRelation('father', null);
                $person->setRelation('mother', null);
                $person->setRelation('partners', collect());
                $person->setRelation('children', collect());

                return $person;
            })
            ->filter()
            ->values();

        return new LengthAwarePaginator(
            items: $items,
            total: $projection->count(),
            perPage: $perPage,
            currentPage: $page,
            options: [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }
}
