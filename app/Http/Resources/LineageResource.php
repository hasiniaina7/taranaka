<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Lineage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialize the stable public summary of a lineage without leaking pivot or model internals.
 *
 * This Resource exists because lineage detail and search need an explicit versioned contract;
 * callers may rely on the identifier, slug, description, and preloaded member count only.
 *
 * @mixin Lineage
 */
class LineageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'member_count' => (int) ($this->people_count ?? 0),
        ];
    }
}
