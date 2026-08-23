<?php

declare(strict_types=1);

namespace App\Actions\People;

use App\Models\Activity;
use App\Models\Person;
use App\Models\User;

/**
 * Records each contributor decision made at the duplicate-warning soft gate.
 *
 * Both resolution paths use one stable Activitylog contract so later audits can distinguish a
 * reused record from a deliberately created one and inspect every candidate score involved.
 */
class RecordDuplicateResolution
{
    public const string CONFIRMED_DISTINCT = 'duplicate_confirmed_distinct';

    public const string LINKED_AS_SAME = 'duplicate_linked_as_same';

    public function linkedAsSame(User $user, Person $person, float $score): void
    {
        $this->record(
            user: $user,
            subject: $person,
            resolution: self::LINKED_AS_SAME,
            candidates: [['id' => $person->id, 'score' => $score]],
            description: "Creation abandoned after matching existing person #{$person->id} (score {$score})",
        );
    }

    /** @param list<array{id: int, score: float}> $candidates */
    public function confirmedDistinct(User $user, Person $person, array $candidates): void
    {
        $primaryCandidate = $candidates[0];

        $this->record(
            user: $user,
            subject: $person,
            resolution: self::CONFIRMED_DISTINCT,
            candidates: $candidates,
            description: "Created after confirming distinct from candidate #{$primaryCandidate['id']} (score {$primaryCandidate['score']})",
        );
    }

    /** @param list<array{id: int, score: float}> $candidates */
    protected function record(User $user, Person $subject, string $resolution, array $candidates, string $description): void
    {
        activity()
            ->useLog('person_couple')
            ->performedOn($subject)
            ->causedBy($user)
            ->event($resolution)
            ->withProperties([
                'resolution' => $resolution,
                'candidates' => $candidates,
            ])
            ->tap(function (Activity $activity) use ($user): void {
                $activity->team_id = $user->currentTeam?->id;
            })
            ->log($description);
    }
}
