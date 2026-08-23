<?php

declare(strict_types=1);

namespace App\Actions\Api;

use App\Models\Couple;
use App\Models\Person;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Hydrate the public family graph without allowing an authenticated caller's team scope to hide links.
 *
 * The graph loading is extracted because person detail is the only API projection that needs family
 * references. Callers receive the same Person instances with father, mother, partners, and children
 * loaded explicitly; serializers may rely on those relations without issuing hidden scoped queries.
 */
class LoadPublicPersonGraph
{
    /**
     * @param  EloquentCollection<int, Person>  $people
     * @return EloquentCollection<int, Person>
     */
    public function execute(EloquentCollection $people): EloquentCollection
    {
        if ($people->isEmpty()) {
            return $people;
        }

        $personIds = $people->modelKeys();
        $couples   = Couple::withoutGlobalScope('team')
            ->where(function ($query) use ($personIds): void {
                $query->whereIn('person1_id', $personIds)
                    ->orWhereIn('person2_id', $personIds);
            })
            ->get(['person1_id', 'person2_id']);

        $relatedIds = $people
            ->flatMap(fn (Person $person): array => [$person->father_id, $person->mother_id])
            ->merge($couples->pluck('person1_id'))
            ->merge($couples->pluck('person2_id'))
            ->filter()
            ->map(fn (mixed $personId): int => (int) $personId)
            ->unique();

        $children = Person::withoutGlobalScope('team')
            ->where(function ($query) use ($personIds): void {
                $query->whereIn('father_id', $personIds)
                    ->orWhereIn('mother_id', $personIds);
            })
            ->orderBy('dob')
            ->get();

        $relatedPeople = Person::withoutGlobalScope('team')
            ->whereKey($relatedIds)
            ->get()
            ->keyBy('id');

        $people->each(function (Person $person) use ($children, $couples, $relatedPeople): void {
            $partnerIds = $couples
                ->filter(fn (Couple $couple): bool => $couple->person1_id === $person->id || $couple->person2_id === $person->id)
                ->map(fn (Couple $couple): int => $couple->person1_id === $person->id ? $couple->person2_id : $couple->person1_id);

            $person->setRelation('father', $relatedPeople->get($person->father_id));
            $person->setRelation('mother', $relatedPeople->get($person->mother_id));
            $person->setRelation('partners', $partnerIds->map(fn (int $partnerId): ?Person => $relatedPeople->get($partnerId))->filter()->values());
            $person->setRelation('children', $children->filter(
                fn (Person $child): bool => $child->father_id === $person->id || $child->mother_id === $person->id,
            )->values());
        });

        return $people;
    }
}
