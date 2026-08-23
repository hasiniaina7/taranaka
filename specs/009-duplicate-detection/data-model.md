# Phase 1 Data Model: Duplicate Detection

No new tables. No new columns on `Person`.

## `PersonSimilarityScorer` (new service, pure function)

```php
final class PersonSimilarityScorer
{
    public function score(PersonCandidateInput $a, PersonCandidateInput $b): float
    {
        // returns 0.0–1.0, per research.md's weighting formula
    }
}
```

`PersonCandidateInput` is a small value object (`name`, `?int $birthYear`)
— deliberately not a full `Person` model dependency, so the scorer stays
unit-testable with plain fixtures (research.md's rationale for separating
retrieval from scoring).

## Derived: Duplicate Resolution Decision (traceability only, FR-005)

Not a new table for MVP — recorded as a note on the created `Person` via
the existing `Activitylog` mechanism already attached to `Person`
(`getActivitylogOptions()`), logging either "created after confirming
distinct from candidate #{id} (score {n})" or, for the "same person" path,
no new `Person` row is created at all (the contributor is redirected to the
existing record — nothing to log on a record that wasn't created).

## `Person::scopeSimilarTo()` (modified)

- Existing signature retained; the hard `team_id` filter (when `$teamId`
  passed) is bypassed for this feature's call site specifically — FR-003
  requires cross-team candidate search, achieved via the same
  `withoutGlobalScope('team')` pattern as spec 006, applied at the call
  site in `PersonForm`, not by changing the scope's default behavior for
  its other existing callers.

## State / Lifecycle

None — synchronous, per-request scoring at person-creation time only (no
background/batch scan, per spec.md Assumptions).
