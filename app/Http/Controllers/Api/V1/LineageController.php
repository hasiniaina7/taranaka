<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LineageResource;
use App\Models\Lineage;

/**
 * Expose one public lineage summary through the version-one read contract.
 *
 * This controller is deliberately separate from member listing so each endpoint has one reason to
 * change. Callers rely on a stable identifier, slug, description, and recorded membership count.
 */
class LineageController extends Controller
{
    public function __invoke(int $lineage): LineageResource
    {
        return LineageResource::make(Lineage::query()->withCount('people')->findOrFail($lineage));
    }
}
