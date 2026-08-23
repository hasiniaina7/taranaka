<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class PersonPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Person $person): bool
    {
        return $user->is_developer || $person->team_id === $user->currentTeam?->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Person $person): bool
    {
        return $this->update($user, $person);
    }

    /**
     * Determine whether the user can toggle the person's public-visibility
     * opt-in (spec 007 FR-004) — reuses the ownership/edit-rights rule
     * verbatim.
     */
    public function togglePrivacy(User $user, Person $person): bool
    {
        return $this->update($user, $person);
    }
}
