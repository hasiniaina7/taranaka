<?php

declare(strict_types=1);

namespace App\Actions\Search;

use App\Models\Person;
use App\Support\PersonPrivacy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

/**
 * Finds people for public discovery without turning team ownership into a visibility boundary.
 *
 * This logic is extracted so autocomplete and full results cannot diverge on the explicit
 * team-scope bypass or privacy projection. Callers receive display-safe arrays only.
 */
class FindPublicPeople
{
    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginated(string $query, int $perPage, string $pageName): LengthAwarePaginator
    {
        $people = $this->query($query)->paginate($perPage, ['*'], $pageName);

        return new Paginator(
            items: $people->getCollection()->map(fn (Person $person): array => $this->project($person)),
            total: $people->total(),
            perPage: $people->perPage(),
            currentPage: $people->currentPage(),
            options: $people->getOptions(),
        );
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function limited(string $query, int $limit): Collection
    {
        return $this->query($query)
            ->limit($limit)
            ->get()
            ->map(fn (Person $person): array => $this->project($person));
    }

    /** @return Builder<Person> */
    protected function query(string $query): Builder
    {
        return Person::withoutGlobalScope('team')
            ->with('lineages:id,name,slug')
            ->search($query)
            ->orderBy('surname')
            ->orderBy('firstname')
            ->orderBy('id');
    }

    /**
     * Reuses the shared privacy projection without hydrating graph relations search never exposes.
     *
     * @return array<string, mixed>
     */
    protected function project(Person $person): array
    {
        $person->setRelation('father', null);
        $person->setRelation('mother', null);
        $person->setRelation('partners', collect());
        $person->setRelation('children', collect());

        $fields  = PersonPrivacy::publicFields($person);
        $private = ! PersonPrivacy::isPubliclyVisible($person);

        return [
            'id'       => $person->id,
            'name'     => mb_trim(implode(' ', array_filter([$fields['firstname'], $fields['surname']]))),
            'url'      => route('public.people.show', $person),
            'private'  => $private,
            'lifespan' => $private ? null : $person->lifetime,
            'lineages' => $private
                ? []
                : $person->lineages
                    ->map(fn ($lineage): array => [
                        'name' => $lineage->name,
                        'url'  => route('lineages.show', $lineage),
                    ])
                    ->all(),
        ];
    }
}
