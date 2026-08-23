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
        return $this->lineage->people()->get()->filter(function ($person): bool {
            if (class_exists(PersonPrivacy::class)) {
                return PersonPrivacy::isPubliclyVisible($person);
            }

            // Temporary fallback until spec 007 (PersonPrivacy) lands: only deceased
            // people are publicly visible by default (FR-009).
            return $person->isDeceased();
        })->values();
    }
};
