<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\PaginateTraversalPeople;
use App\Actions\BuildDescendantNodes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TraversalRequest;
use App\Http\Resources\PersonResource;
use App\Models\Person;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Project the existing public descendant engine through a bounded API collection.
 *
 * Traversal and privacy stay in their shared Action/Resource; this controller only validates
 * bounds, removes the root node as the web list does, and delegates pagination.
 */
class DescendantsController extends Controller
{
    public function __invoke(int $person): AnonymousResourceCollection
    {
        $request     = app(TraversalRequest::class);
        $personModel = Person::withoutGlobalScope('team')->findOrFail($person);
        $projection  = app(BuildDescendantNodes::class)
            ->execute($personModel, $request->maxDepth())
            ->where('degree', '>', 0)
            ->unique('id')
            ->map(fn (array $node): array => [
                'id'     => (int) $node['id'],
                'degree' => (int) $node['degree'],
            ])
            ->values();

        $paginator = app(PaginateTraversalPeople::class)
            ->execute($projection, $request->perPage(), $request->page())
            ->appends($request->except('page'));

        return PersonResource::collection($paginator);
    }
}
