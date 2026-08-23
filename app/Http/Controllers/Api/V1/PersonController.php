<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Api\LoadPublicPersonGraph;
use App\Http\Controllers\Controller;
use App\Http\Resources\PersonResource;
use App\Models\Person;

/**
 * Expose one public person profile through the version-one read contract.
 *
 * The controller only resolves the cross-team entity and delegates graph loading and privacy
 * serialization. Callers receive the same public profile regardless of authentication state.
 */
class PersonController extends Controller
{
    public function __invoke(int $person): PersonResource
    {
        $personModel = Person::withoutGlobalScope('team')
            ->with('lineages:id,name,slug')
            ->findOrFail($person);

        app(LoadPublicPersonGraph::class)->execute($personModel->newCollection([$personModel]));

        return PersonResource::profile($personModel);
    }
}
