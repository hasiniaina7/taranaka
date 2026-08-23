<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Lineage;
use App\Models\Person;
use App\Support\PersonPrivacy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Serialize the public Person projection without exposing raw models or exact private attributes.
 *
 * The Resource is shared by detail and list endpoints so privacy cannot drift. Callers may request
 * the profile graph explicitly; compact collections retain only fields visible in public explorers.
 *
 * @mixin Person
 */
class PersonResource extends JsonResource
{
    protected bool $includeProfileGraph = false;

    public static function profile(Person $person): self
    {
        $resource                      = new self($person);
        $resource->includeProfileGraph = true;

        return $resource;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Person $person */
        $person   = $this->resource;
        $fields   = PersonPrivacy::publicFields($person);
        $lineages = $fields['lineages'] instanceof Collection ? $fields['lineages'] : collect();

        $data = [
            'id'        => $person->id,
            'name'      => mb_trim(implode(' ', array_filter([$fields['firstname'], $fields['surname']]))),
            'lifespan'  => $this->lifespan($fields['yob'], $fields['yod']),
            'photo_url' => $this->photoUrl($person, $fields['photo']),
            'lineages'  => $lineages->map(fn (Lineage $lineage): array => [
                'id'   => $lineage->id,
                'name' => $lineage->name,
                'slug' => $lineage->slug,
            ])->values()->all(),
            'is_private' => ! PersonPrivacy::isPubliclyVisible($person),
        ];

        if ($person->getAttribute('degree') !== null) {
            $data['degree'] = (int) $person->getAttribute('degree');
        }

        if (! $this->includeProfileGraph) {
            return $data;
        }

        return [
            ...$data,
            'summary'  => $fields['summary'],
            'parents'  => $this->references(collect([$fields['father'], $fields['mother']])),
            'partners' => $this->references($fields['partners']),
            'children' => $this->references($fields['children']),
        ];
    }

    protected function lifespan(mixed $birthYear, mixed $deathYear): ?string
    {
        if ($birthYear === null && $deathYear === null) {
            return null;
        }

        return ($birthYear ?? '?') . '–' . ($deathYear ?? 'living');
    }

    protected function photoUrl(Person $person, mixed $photo): ?string
    {
        if (! is_string($photo) || $photo === '') {
            return null;
        }

        $path = "{$person->team_id}/{$person->id}/{$photo}_medium.webp";

        return Storage::disk('photos')->exists($path)
            ? Storage::disk('photos')->url($path)
            : null;
    }

    /**
     * @return list<array{id:int, name:string}>
     */
    protected function references(mixed $people): array
    {
        return collect($people)
            ->filter(fn (mixed $person): bool => $person instanceof Person)
            ->map(fn (Person $person): array => [
                'id'   => $person->id,
                'name' => $person->name,
            ])
            ->values()
            ->all();
    }
}
