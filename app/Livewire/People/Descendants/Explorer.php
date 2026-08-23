<?php

declare(strict_types=1);

namespace App\Livewire\People\Descendants;

use App\Models\Person;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Coordinate the public descendant explorer's view and traversal bound.
 *
 * This state is kept above the tree and list so switching presentations never
 * changes the selected root or depth. Callers may initialize only the root person.
 */
class Explorer extends Component
{
    #[Locked]
    public int $personId;

    public string $view = 'tree';

    public int $maxDepth = 3;

    public function mount(Person $person): void
    {
        $this->personId = $person->id;
    }

    public function showTree(): void
    {
        $this->view = 'tree';
    }

    public function showList(): void
    {
        $this->view = 'list';
    }

    public function updatedMaxDepth(int|string $maxDepth): void
    {
        $this->maxDepth = max(1, min(10, (int) $maxDepth));
    }

    #[Computed]
    public function person(): Person
    {
        return Person::withoutGlobalScope('team')->findOrFail($this->personId);
    }

    #[Computed]
    public function photoUrl(): ?string
    {
        if (! $this->person->photo) {
            return null;
        }

        $path = "{$this->person->team_id}/{$this->person->id}/{$this->person->photo}_medium.webp";

        return Storage::disk('photos')->exists($path)
            ? Storage::disk('photos')->url($path)
            : null;
    }

    public function render(): View
    {
        return view('livewire.people.descendants.explorer', [
            'person' => $this->person,
        ]);
    }
}
