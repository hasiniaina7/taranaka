<?php

declare(strict_types=1);

use App\Models\Person;
use App\Support\PersonPrivacy;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

new class extends Component
{
    use Interactions;

    // -----------------------------------------------------------------------
    public Person $person;

    public bool $is_publicly_visible = false;

    // -----------------------------------------------------------------------
    public function mount(): void
    {
        Gate::authorize('togglePrivacy', $this->person);

        $this->is_publicly_visible = (bool) $this->person->is_publicly_visible;
    }

    public function isLiving(): bool
    {
        return PersonPrivacy::isLiving($this->person);
    }

    public function toggle(): void
    {
        Gate::authorize('togglePrivacy', $this->person);

        $this->person->update([
            'is_publicly_visible' => ! $this->person->is_publicly_visible,
        ]);

        $this->is_publicly_visible = (bool) $this->person->fresh()->is_publicly_visible;

        $this->toast()->success(__('app.save'), __('app.saved'))->send();
    }
};
