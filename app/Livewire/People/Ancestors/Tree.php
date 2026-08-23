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
 * Progressively reveal the parent branches of one public genealogy tree.
 *
 * Branch loading is isolated here because each expansion is its own Livewire
 * request. Callers can rely on already-loaded branches remaining cached when
 * collapsed and on missing parents being represented only in the view model.
 */
class Tree extends Component
{
    #[Locked]
    public int $personId;

    #[Locked]
    public int $maxDepth;

    /** @var array<int, array{id:int, firstname:string|null, surname:string|null, father_id:int|null, mother_id:int|null, yob:int|null, degree:int, depth:int, living:bool}> */
    public array $nodes = [];

    /** @var list<int> */
    public array $expandedNodeIds = [];

    /** @var list<int> */
    public array $loadedNodeIds = [];

    public function mount(int $personId, int $maxDepth = 3): void
    {
        $this->personId = $personId;
        $this->maxDepth = max(1, min(128, $maxDepth));

        $this->loadBranch($personId, 0);
        $this->expandedNodeIds = [$personId];
    }

    public function toggleBranch(int $personId): void
    {
        $node = $this->nodes[$personId] ?? null;

        if ($node === null || (int) $node['depth'] >= $this->maxDepth || ! $this->hasRecordedParent($node)) {
            return;
        }

        if (in_array($personId, $this->expandedNodeIds, true)) {
            $this->expandedNodeIds = array_values(array_diff($this->expandedNodeIds, [$personId]));

            return;
        }

        $this->loadBranch($personId, (int) $node['depth']);
        $this->expandedNodeIds[] = $personId;
    }

    /** @return array<string, mixed> */
    public function tree(): array
    {
        $root = $this->nodes[$this->personId] ?? null;

        if ($root === null) {
            return [];
        }

        return $this->buildNode($root, []);
    }

    public function limitReached(): bool
    {
        return collect($this->nodes)->contains(
            fn (array $node): bool => $node['depth'] >= $this->maxDepth && $this->hasRecordedParent($node),
        );
    }

    public function render(): View
    {
        return view('livewire.people.ancestors.tree');
    }

    protected function loadBranch(int $personId, int $parentDepth): void
    {
        if (in_array($personId, $this->loadedNodeIds, true)) {
            return;
        }

        $ancestors = app(AncestorsQueryInterface::class)->getAncestors($personId, 1);
        $people    = Person::withoutGlobalScope('team')
            ->whereKey($ancestors->pluck('id'))
            ->get()
            ->keyBy('id');

        foreach ($ancestors as $ancestor) {
            $person = $people->get((int) $ancestor->id);

            if ($person === null) {
                continue;
            }

            $nodeId = (int) $ancestor->id;
            $node   = [
                'id'        => $nodeId,
                'firstname' => $ancestor->firstname,
                'surname'   => $ancestor->surname,
                'father_id' => $ancestor->father_id === null ? null : (int) $ancestor->father_id,
                'mother_id' => $ancestor->mother_id === null ? null : (int) $ancestor->mother_id,
                'yob'       => $ancestor->yob === null ? null : (int) $ancestor->yob,
                'degree'    => (int) $ancestor->degree,
                'depth'     => $parentDepth + (int) $ancestor->degree,
                'living'    => PersonPrivacy::isLiving($person),
            ];

            if (! isset($this->nodes[$nodeId]) || (int) $node['depth'] < (int) $this->nodes[$nodeId]['depth']) {
                $this->nodes[$nodeId] = $node;
            }
        }

        $this->loadedNodeIds[] = $personId;
    }

    /**
     * @param  array<string, bool|int|string|null>  $node
     * @param  list<int>  $path
     * @return array<string, mixed>
     */
    protected function buildNode(array $node, array $path): array
    {
        $nodeId   = (int) $node['id'];
        $depth    = (int) $node['depth'];
        $isCycle  = in_array($nodeId, $path, true);
        $expanded = ! $isCycle && in_array($nodeId, $this->expandedNodeIds, true);

        $treeNode = [
            ...$node,
            'name'      => $this->personName($node),
            'expanded'  => $expanded,
            'canExpand' => ! $isCycle && $depth < $this->maxDepth && $this->hasRecordedParent($node),
            'children'  => [],
        ];

        if (! $expanded || $depth >= $this->maxDepth || ! $this->hasRecordedParent($node)) {
            return $treeNode;
        }

        $path[] = $nodeId;

        foreach (['father_id', 'mother_id'] as $relationship) {
            $parentId = $node[$relationship];

            if ($parentId === null || ! isset($this->nodes[(int) $parentId])) {
                $treeNode['children'][] = [
                    'unknown'      => true,
                    'relationship' => $relationship,
                ];

                continue;
            }

            $parent                 = $this->nodes[(int) $parentId];
            $parent['depth']        = $depth + 1;
            $treeNode['children'][] = $this->buildNode($parent, $path);
        }

        return $treeNode;
    }

    /** @param array<string, bool|int|string|null> $node */
    protected function hasRecordedParent(array $node): bool
    {
        return $node['father_id'] !== null || $node['mother_id'] !== null;
    }

    /** @param array<string, bool|int|string|null> $node */
    protected function personName(array $node): string
    {
        $name = mb_trim(implode(' ', array_filter([
            $node['firstname'],
            $node['surname'],
        ])));

        return $name !== '' ? $name : 'Nom inconnu';
    }
}
