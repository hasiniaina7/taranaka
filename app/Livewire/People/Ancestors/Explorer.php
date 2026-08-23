<?php

declare(strict_types=1);

namespace App\Livewire\People\Ancestors;

use App\Models\Person;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Coordinate the public ancestor explorer's view and depth controls.
 *
 * The shell is extracted from the tree and list so both projections share
 * one bounded traversal setting and callers can switch views consistently.
 */
class Explorer extends Component
{
    #[Locked]
    public int $personId;

    public int $maxDepth = 3;

    public string $view = 'tree';

    public function mount(int $personId): void
    {
        Person::withoutGlobalScopes()->findOrFail($personId);

        $this->personId = $personId;
    }

    public function showTree(): void
    {
        $this->view = 'tree';
    }

    public function showList(): void
    {
        $this->view = 'list';
    }

    public function updatedMaxDepth(): void
    {
        $this->maxDepth = max(1, min(128, $this->maxDepth));
    }

    public function render(): View
    {
        return view('livewire.people.ancestors.explorer', [
            'person' => Person::withoutGlobalScope('team')->findOrFail($this->personId),
        ]);
    }
}
