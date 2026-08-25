<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\PaginateLineageMembers;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaginationRequest;
use App\Http\Resources\PersonResource;
use App\Models\Lineage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Expose the privacy-filtered members of one lineage as a bounded collection.
 *
 * Member querying is delegated to the shared privacy Action so this single-action controller only
 * resolves the lineage and pagination contract. Callers never receive hidden living members.
 */
class LineageMembersController extends Controller
{
    public function __invoke(int $lineage): AnonymousResourceCollection
    {
        $request      = app(PaginationRequest::class);
        $lineageModel = Lineage::query()->findOrFail($lineage);
        $paginator    = app(PaginateLineageMembers::class)
            ->execute($lineageModel, $request->perPage())
            ->appends($request->except('page'));

        return PersonResource::collection($paginator);
    }
}
