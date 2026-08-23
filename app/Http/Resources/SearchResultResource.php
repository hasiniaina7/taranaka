<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Preserve the two independently paginated result groups returned by the public search Actions.
 *
 * A dedicated Resource keeps pagination metadata consistent while leaving search ordering and
 * privacy projection in the same Actions used by the web. Callers receive top-level `people`
 * and `lineages` groups, each with standard data, links, and meta keys.
 */
class SearchResultResource extends JsonResource
{
    public static $wrap = null;

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var array{people:LengthAwarePaginator, lineages:LengthAwarePaginator} $paginators */
        $paginators = $this->resource;

        return [
            'people'   => $this->pagination($paginators['people']),
            'lineages' => $this->pagination($paginators['lineages']),
        ];
    }

    /** @return array<string, mixed> */
    protected function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'data'  => $paginator->items(),
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ];
    }
}
