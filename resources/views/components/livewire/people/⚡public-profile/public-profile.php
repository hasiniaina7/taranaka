<?php

declare(strict_types=1);

use App\Models\Person;
use App\Support\PersonPrivacy;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    // -----------------------------------------------------------------------
    #[Locked]
    public Person $person;

    // -----------------------------------------------------------------------
    public function mount(Person $person): void
    {
        $person->load(['father', 'mother', 'lineages', 'children']);

        $this->person = $person;
    }

    // -----------------------------------------------------------------------
    /** @return array<string, mixed> */
    public function fields(): array
    {
        return PersonPrivacy::publicFields($this->person);
    }

    public function isPubliclyVisible(): bool
    {
        return PersonPrivacy::isPubliclyVisible($this->person);
    }

    public function photoUrl(): ?string
    {
        if (! $this->person->photo) {
            return null;
        }

        $path = "{$this->person->team_id}/{$this->person->id}/{$this->person->photo}_medium.webp";

        return Storage::disk('photos')->exists($path) ? Storage::disk('photos')->url($path) : null;
    }
};
