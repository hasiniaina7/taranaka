<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contribution;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

final class ContributionPolicy
{
    use HandlesAuthorization;

    /**
     * Any registered user may propose a change (FR-001).
     */
    public function propose(User $user): bool
    {
        return true;
    }

    /**
     * The author or a moderator may view a contribution.
     */
    public function view(User $user, Contribution $contribution): bool
    {
        return $user->id === $contribution->author_id || $user->isModerator();
    }

    /**
     * Only a moderator, and never the contribution's own author
     * (self-review guard, data-model.md), may decide on it.
     */
    public function decide(User $user, Contribution $contribution): bool
    {
        return $user->isModerator() && $user->id !== $contribution->author_id;
    }

    public function accept(User $user, Contribution $contribution): bool
    {
        return $this->decide($user, $contribution);
    }

    public function reject(User $user, Contribution $contribution): bool
    {
        return $this->decide($user, $contribution);
    }
}
