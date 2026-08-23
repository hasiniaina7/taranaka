<?php

declare(strict_types=1);

use App\Models\Contribution;
use App\Models\Couple;
use App\Models\Person;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    // -----------------------------------------------------------------------
    #[Locked]
    public Contribution $contribution;

    public string $rejection_reason = '';

    // -----------------------------------------------------------------------
    public function mount(Contribution $contribution): void
    {
        Gate::authorize('view', $contribution);

        $this->contribution = $contribution;
    }

    public function isStillApplicable(): bool
    {
        return $this->contribution->isStillApplicable();
    }

    public function accept(): void
    {
        Gate::authorize('accept', $this->contribution);

        match ($this->contribution->target_type) {
            Contribution::TARGET_PERSON, Contribution::TARGET_COUPLE => $this->applyFieldChange(),
            Contribution::TARGET_PERSON_NEW                          => $this->applyNewPerson(),
            Contribution::TARGET_RELATIONSHIP_NEW                    => $this->applyNewRelationship(),
            default                                                  => null,
        };

        $this->contribution->update([
            'status'      => Contribution::STATUS_ACCEPTED,
            'reviewer_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->toast()->success(__('contributions.accepted'), __('contributions.accepted_hint'))->send();
    }

    public function reject(): void
    {
        Gate::authorize('reject', $this->contribution);

        $this->validate(['rejection_reason' => ['required', 'string', 'max:65535']]);

        $this->contribution->update([
            'status'           => Contribution::STATUS_REJECTED,
            'reviewer_id'      => auth()->id(),
            'reviewed_at'      => now(),
            'rejection_reason' => $this->rejection_reason,
        ]);

        $this->toast()->success(__('contributions.rejected'), __('contributions.rejected_hint'))->send();
    }

    // -----------------------------------------------------------------------
    private function applyFieldChange(): void
    {
        $target = $this->contribution->target();

        if ($target === null || $this->contribution->field === null) {
            return;
        }

        $target->update([$this->contribution->field => $this->contribution->new_value]);
    }

    private function applyNewPerson(): void
    {
        /** @var array<string, mixed> $data */
        $data   = json_decode($this->contribution->new_value, true) ?? [];
        $parent = Person::withoutGlobalScope('team')->find($data['parent_id'] ?? null);

        if ($parent === null || $this->contribution->field === null) {
            return;
        }

        $child = Person::create([
            'firstname'                => $data['firstname'] ?? null,
            'surname'                  => $data['surname'] ?? null,
            'sex'                      => $data['sex'] ?? 'm',
            $this->contribution->field => $parent->id,
            'team_id'                  => $parent->team_id,
        ]);

        $this->contribution->target_id = $child->id;
    }

    private function applyNewRelationship(): void
    {
        /** @var array<string, mixed> $data */
        $data    = json_decode($this->contribution->new_value, true) ?? [];
        $person1 = Person::withoutGlobalScope('team')->find($data['person1_id'] ?? null);
        $person2 = Person::withoutGlobalScope('team')->find($data['person2_id'] ?? null);

        if ($person1 === null || $person2 === null) {
            return;
        }

        $couple = Couple::create([
            'person1_id' => $person1->id,
            'person2_id' => $person2->id,
            'team_id'    => $person1->team_id,
        ]);

        $this->contribution->target_id = $couple->id;
    }
};
