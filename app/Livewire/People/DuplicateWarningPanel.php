<?php

declare(strict_types=1);

namespace App\Livewire\People;

use Illuminate\View\View;
use Livewire\Attributes\Reactive;
use Livewire\Component;

/**
 * Presents ranked duplicate candidates and emits the contributor's chosen resolution.
 *
 * Detection and authorization remain in the parent creation flow; callers can rely on this child
 * to render privacy-safe arrays only and never create, update, or query a Person itself.
 */
class DuplicateWarningPanel extends Component
{
    /**
     * @var list<array{id: int, name: string, lifespan: ?string, lineages: list<string>, private: bool, score: float, percentage: int, high_confidence: bool, url: string}>
     */
    #[Reactive]
    public array $candidates = [];

    public function hasHighConfidence(): bool
    {
        return collect($this->candidates)->contains('high_confidence', true);
    }

    public function reuse(int $candidateId): void
    {
        abort_unless(collect($this->candidates)->contains('id', $candidateId), 404);

        $this->dispatch('duplicate-existing-selected', candidateId: $candidateId);
    }

    public function acknowledgeDistinct(): void
    {
        abort_unless($this->hasHighConfidence(), 422);

        $this->dispatch('duplicate-candidates-acknowledged');
    }

    public function render(): View
    {
        return view('livewire.people.duplicate-warning-panel');
    }
}
