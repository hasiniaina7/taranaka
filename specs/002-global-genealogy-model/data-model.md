# Phase 1 Data Model: Global Genealogy Model

No new tables or columns. This spec changes *query/authorization behavior*
over the existing `Person`/`Couple` schema.

## Modified: `Person` global scope (`app/Models/Person.php::booted()`)

Current behavior (000 audit):
```php
self::addGlobalScope('team', function (Builder $builder): void {
    $user = auth()->user();
    if (! $user || $user->is_developer) { return; }
    $currentTeam = $user->currentTeam;
    if ($currentTeam) {
        $builder->where('people.team_id', $currentTeam->id);
    }
});
```

New predicate shape (FR-003), conceptually:
```php
$builder->where(function ($q) use ($currentTeam) {
    $q->where('people.team_id', $currentTeam->id)
      ->orWhereIn('people.id', $reachableFromCurrentTeamSubquery);
});
```

Where `$reachableFromCurrentTeamSubquery` selects person IDs connected via
`couples.person1_id`/`person2_id` or `people.father_id`/`mother_id`/
`parents_id` to a person already in `team_id = $currentTeam->id`. Exact SQL
shape (subquery vs. join) is an implementation-phase decision, not fixed
here — both are valid depending on Larastan/query-performance findings
during implementation.

`Couple::booted()` gets the symmetric change.

## New: `PersonPolicy`

| Method | Rule (FR-004) |
|---|---|
| `update(User $user, Person $person): bool` | `true` only if `$person->team_id === $user->currentTeam?->id`, or `$user->is_developer`. |
| `delete(User $user, Person $person): bool` | Same rule as `update` (mirrors existing `isDeletable()` pattern's authorization intent). |

This policy is registered and consumed via Laravel's standard
`$this->authorize()` / `@can` mechanism, per existing Laravel Boost
guidance ("Use Laravel's built-in authentication and authorization
features").

## State / Lifecycle

No new state. `team_id` on `Person`/`Couple` continues to be set once at
creation (existing behavior, e.g. `Person::booted()`'s implicit team
assignment on create) and is not reassigned by this spec.
