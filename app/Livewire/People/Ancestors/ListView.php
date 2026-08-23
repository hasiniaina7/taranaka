<?php

declare(strict_types=1);

namespace App\Livewire\People\Ancestors;

use App\Contracts\AncestorsQueryInterface;
use App\Models\Person;
use App\Support\PersonPrivacy;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Present the complete bounded ancestor traversal as a filterable table.
 *
 * The list projection is kept apart from the progressive tree because it
 * intentionally loads the full selected depth and enriches rows with lineage
 * names; callers can filter by genealogy generation without changing data.
 */
class ListView extends Component
{
    #[Locked]
    public int $personId;

    #[Locked]
    public int $maxDepth;

    public int|string|null $generationFilter = null;

    /** @var list<array{id:int, generation:int, name:string, lineage:string, birth_year:int|string|null, living:bool, url:string}> */
    public array $ancestors = [];

    public bool $limitReached = false;

    public function mount(int $personId, int $maxDepth = 3): void
    {
        $this->personId = $personId;
        $this->maxDepth = max(1, min(128, $maxDepth));

        $this->loadAncestors();
    }

    /** @return list<array{id:int, generation:int, name:string, lineage:string, birth_year:int|string|null, living:bool, url:string}> */
    public function rows(): array
    {
        if ($this->generationFilter === null || $this->generationFilter === '') {
            return $this->ancestors;
        }

        $generation = (int) $this->generationFilter;

        return array_values(array_filter(
            $this->ancestors,
            fn (array $ancestor): bool => $ancestor['generation'] === $generation,
        ));
    }

    /** @return list<array{label:string, value:int|string}> */
    public function generationOptions(): array
    {
        $generations = collect($this->ancestors)
            ->pluck('generation')
            ->unique()
            ->sort()
            ->values()
            ->map(fn (int $generation): array => [
                'label' => "Génération {$generation}",
                'value' => $generation,
            ])
            ->all();

        return [
            ['label' => 'Toutes les générations', 'value' => ''],
            ...$generations,
        ];
    }

    public function render(): View
    {
        return view('livewire.people.ancestors.list-view');
    }

    protected function loadAncestors(): void
    {
        $ancestors = app(AncestorsQueryInterface::class)
            ->getAncestors($this->personId, $this->maxDepth)
            ->where('degree', '>', 0)
            ->values();

        $people = Person::withoutGlobalScope('team')
            ->with('lineages')
            ->whereKey($ancestors->pluck('id'))
            ->get()
            ->keyBy('id');

        $this->limitReached = $ancestors->contains(
            fn (object $ancestor): bool => (int) $ancestor->degree >= $this->maxDepth
                && ($ancestor->father_id !== null || $ancestor->mother_id !== null),
        );

        $this->ancestors = $ancestors
            ->map(function (object $ancestor) use ($people): array {
                $person = $people->get((int) $ancestor->id);
                $name   = mb_trim(implode(' ', array_filter([$ancestor->firstname, $ancestor->surname])));

                return [
                    'id'         => (int) $ancestor->id,
                    'generation' => (int) $ancestor->degree,
                    'name'       => $name !== '' ? $name : 'Nom inconnu',
                    'lineage'    => $person?->lineages->pluck('name')->join(', ') ?: '—',
                    'birth_year' => $ancestor->yob,
                    'living'     => $person === null || PersonPrivacy::isLiving($person),
                    'url'        => route('public.people.show', (int) $ancestor->id),
                ];
            })
            ->all();
    }
}
