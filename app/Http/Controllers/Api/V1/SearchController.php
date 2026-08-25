<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Search\FindPublicLineages;
use App\Actions\Search\FindPublicPeople;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchRequest;
use App\Http\Resources\SearchResultResource;

/**
 * Expose the shared public search projections through a versioned JSON contract.
 *
 * Search ordering, cross-team behavior, and privacy remain in the Actions used by Livewire;
 * callers receive independently paginated and distinguishable people and lineage groups.
 */
class SearchController extends Controller
{
    public function __invoke(): SearchResultResource
    {
        $request = app(SearchRequest::class);
        $query   = $request->queryText();
        $perPage = $request->perPage();

        return SearchResultResource::make([
            'people'   => app(FindPublicPeople::class)->paginated($query, $perPage, 'page'),
            'lineages' => app(FindPublicLineages::class)->paginated($query, $perPage, 'page'),
        ]);
    }
}
