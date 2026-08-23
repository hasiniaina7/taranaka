<?php

declare(strict_types=1);

use App\Livewire\Traits\AuthorizesPersonActions;
use App\Models\Lineage;
use App\Models\Person;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use AuthorizesPersonActions;
    use Interactions;

    // -----------------------------------------------------------------------
    public Person $person;

    public ?int $selectedLineageId = null;

    // -----------------------------------------------------------------------
    /**
     * @return Collection<int, Lineage>
     */
    #[Computed]
    public function availableLineages(): Collection
    {
        $attachedIds = $this->person->lineages()->pluck('lineages.id');

        return Lineage::query()->whereNotIn('id', $attachedIds)->orderBy('name')->get();
    }

    public function attach(?int $lineageId = null): void
    {
        $this->authorizePermission('person:update');

        $lineageId ??= $this->selectedLineageId;

        if (! $lineageId) {
            return;
        }

        $this->person->lineages()->syncWithoutDetaching([$lineageId]);
        $this->selectedLineageId = null;

        unset($this->availableLineages);

        $this->toast()->success(__('app.save'), __('app.saved'))->send();
    }

    public function detach(int $lineageId): void
    {
        $this->authorizePermission('person:update');

        $this->person->lineages()->detach($lineageId);

        unset($this->availableLineages);
    }
};
