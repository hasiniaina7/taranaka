<?php

declare(strict_types=1);

namespace App\Livewire\People\Descendants;

use App\Actions\BuildDescendantNodes;
use App\Models\Person;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

/**
 * Present the bounded descendant set as a searchable, generation-filtered list.
 *
 * Filtering is isolated from the explorer because it applies only to this view.
 * Callers can rely on the same shared node projection used by the tree.
 */
class ListView extends Component
{
    #[Locked]
    public int $personId;

    #[Reactive]
    public int $maxDepth;

    public string $generationFilter = '';

    public string $nameFilter = '';

    public function mount(Person $person, int $maxDepth): void
    {
        $this->personId = $person->id;
        $this->maxDepth = max(1, $maxDepth);
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed(persist: true, seconds: 3600)]
    public function descendants(): Collection
    {
        return app(BuildDescendantNodes::class)
            ->execute(Person::withoutGlobalScope('team')->findOrFail($this->personId), $this->maxDepth)
            ->where('degree', '>', 0)
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    #[Computed]
    public function filteredDescendants(): Collection
    {
        return $this->descendants
            ->when(
                $this->generationFilter !== '',
                fn (Collection $descendants): Collection => $descendants->where('degree', (int) $this->generationFilter),
            )
            ->when(
                $this->nameFilter !== '',
                fn (Collection $descendants): Collection => $descendants->filter(
                    fn (array $descendant): bool => Str::contains($descendant['name'], $this->nameFilter, ignoreCase: true),
                ),
            )
            ->values();
    }

    /** @return list<int> */
    #[Computed]
    public function generations(): array
    {
        return $this->descendants
            ->pluck('degree')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.people.descendants.list-view');
    }
}
