<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\DescendantsQueryInterface;
use App\Models\Person;
use App\Support\PersonPrivacy;
use Illuminate\Support\Collection;

/**
 * Build the shared, privacy-aware node projection used by descendant views.
 *
 * This logic is extracted so the tree and list cannot drift into querying or
 * exposing different people. Callers can rely on the requested depth being
 * forwarded unchanged and on each row retaining its traversal sequence.
 */
class BuildDescendantNodes
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     name: string,
     *     degree: int,
     *     sequence: string,
     *     parent_sequence: string|null,
     *     birth_year: int|null,
     *     death_year: int|null,
     *     is_living: bool,
     *     lineages: list<string>
     * }>
     */
    public function execute(Person $person, int $maxDepth): Collection
    {
        $rows = app(DescendantsQueryInterface::class)
            ->getDescendants($person->id, $maxDepth);

        $people = Person::withoutGlobalScope('team')
            ->with('lineages')
            ->whereKey($rows->pluck('id')->map(fn (mixed $id): int => (int) $id))
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function (object $row) use ($people): ?array {
                $person = $people->get((int) $row->id);

                if (! $person instanceof Person) {
                    return null;
                }

                $sequence = (string) $row->sequence;

                return [
                    'id'              => $person->id,
                    'name'            => $person->name,
                    'degree'          => (int) $row->degree,
                    'sequence'        => $sequence,
                    'parent_sequence' => $this->parentSequence($sequence),
                    'birth_year'      => $person->yob,
                    'death_year'      => $person->yod,
                    'is_living'       => PersonPrivacy::isLiving($person),
                    'lineages'        => $person->lineages->pluck('name')->values()->all(),
                ];
            })
            ->filter()
            ->values();
    }

    protected function parentSequence(string $sequence): ?string
    {
        $separatorPosition = mb_strrpos($sequence, ',');

        if ($separatorPosition === false) {
            return null;
        }

        return mb_substr($sequence, 0, $separatorPosition);
    }
}
