<?php

declare(strict_types=1);

use App\Models\Lineage;
use App\Support\PersonPrivacy;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    // -----------------------------------------------------------------------
    #[Locked]
    public Lineage $lineage;

    // -----------------------------------------------------------------------
    /**
     * @return Collection<int, App\Models\Person>
     */
    public function visibleMembers(): Collection
    {
        return $this->lineage->people()->get()
            ->filter(fn ($person): bool => PersonPrivacy::isPubliclyVisible($person))
            ->values();
    }
};
