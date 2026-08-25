<?php

declare(strict_types=1);

namespace App\Livewire\People\Descendants;

use App\Actions\BuildDescendantNodes;
use App\Models\Person;
use App\Support\PersonTreePresentation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

/**
 * Render and progressively reveal the bounded descendant tree.
 *
 * Branch state belongs here because it is presentation-specific. Callers can
 * rely on the root being open initially while every deeper branch starts closed.
 */
class Tree extends Component
{
    #[Locked]
    public int $personId;

    #[Reactive]
    public int $maxDepth;

    /** @var list<int> */
    public array $expandedNodeIds = [];

    public function mount(Person $person, int $maxDepth): void
    {
        $this->personId        = $person->id;
        $this->maxDepth        = max(1, $maxDepth);
        $this->expandedNodeIds = [$person->id];
    }

    public function toggleNode(int $personId): void
    {
        if (in_array($personId, $this->expandedNodeIds, true)) {
            $this->expandedNodeIds = array_values(array_filter(
                $this->expandedNodeIds,
                fn (int $expandedNodeId): bool => $expandedNodeId !== $personId,
            ));

            return;
        }

        $this->expandedNodeIds[] = $personId;
    }

    public function render(): View
    {
        return view('livewire.people.descendants.tree');
    }

    public function limitReached(): bool
    {
        return $this->nodeReachesLimit($this->tree);
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     degree: int,
     *     sequence: string,
     *     parent_sequence: string|null,
     *     birth_year: int|null,
     *     death_year: int|null,
     *     is_living: bool,
     *     lineages: list<string>,
     *     children: list<array<string, mixed>>
     * }
     */
    #[Computed(persist: true, seconds: 3600)]
    public function tree(): array
    {
        $person = Person::withoutGlobalScope('team')->findOrFail($this->personId);
        $nodes  = app(BuildDescendantNodes::class)->execute($person, $this->maxDepth);
        $root   = $nodes->first(fn (array $node): bool => $node['degree'] === 0);

        if (! is_array($root)) {
            return $this->fallbackRoot($person);
        }

        return $this->buildBranch(
            $root,
            $nodes->groupBy('parent_sequence'),
        );
    }

    /** @param array<string, mixed> $node */
    protected function nodeReachesLimit(array $node): bool
    {
        if (($node['degree'] ?? null) === $this->maxDepth) {
            return true;
        }

        foreach ($node['children'] ?? [] as $child) {
            if ($this->nodeReachesLimit($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  Collection<string, Collection<int, array<string, mixed>>>  $nodesByParent
     * @return array<string, mixed>
     */
    protected function buildBranch(array $node, Collection $nodesByParent): array
    {
        $children = $nodesByParent
            ->get($node['sequence'], collect())
            ->map(fn (array $child): array => $this->buildBranch($child, $nodesByParent))
            ->values()
            ->all();

        return [...$node, 'children' => $children];
    }

    /** @return array<string, mixed> */
    protected function fallbackRoot(Person $person): array
    {
        return [
            'id'              => $person->id,
            'name'            => $person->name,
            'degree'          => 0,
            'sequence'        => (string) $person->id,
            'parent_sequence' => null,
            'birth_year'      => $person->yob,
            'death_year'      => $person->yod,
            'is_living'       => ! $person->isDeceased(),
            'lineages'        => [],
            'photo_url'       => PersonTreePresentation::photoUrl($person),
            'partners'        => PersonTreePresentation::partners($person),
            'children'        => [],
        ];
    }
}
