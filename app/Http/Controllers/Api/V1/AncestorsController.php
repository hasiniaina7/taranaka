<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\PaginateTraversalPeople;
use App\Contracts\AncestorsQueryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TraversalRequest;
use App\Http\Resources\PersonResource;
use App\Models\Person;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Project the existing ancestor query engine through a bounded API collection.
 *
 * The controller keeps the recursive query visible and unchanged, then delegates hydration and
 * serialization. Callers receive degree-tagged ancestors without the traversal's root row.
 */
class AncestorsController extends Controller
{
    public function __invoke(int $person): AnonymousResourceCollection
    {
        $request     = app(TraversalRequest::class);
        $personModel = Person::withoutGlobalScope('team')->findOrFail($person);
        $projection  = app(AncestorsQueryInterface::class)
            ->getAncestors($personModel->id, $request->maxDepth())
            ->where('degree', '>', 0)
            ->map(fn (object $row): array => [
                'id'     => (int) $row->id,
                'degree' => (int) $row->degree,
            ])
            ->values();

        $paginator = app(PaginateTraversalPeople::class)
            ->execute($projection, $request->perPage(), $request->page())
            ->appends($request->except('page'));

        return PersonResource::collection($paginator);
    }
}
