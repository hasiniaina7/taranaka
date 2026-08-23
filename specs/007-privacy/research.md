# Phase 0 Research: Privacy Rules for Living People

## Decision: a single boolean column, not a separate table

- **Decision**: `is_publicly_visible` (boolean, default `false`) as a plain
  column on `people`, not a separate `PersonPrivacySetting` table.
- **Rationale**: spec 007's Assumptions section already scopes MVU opt-in
  as coarse-grained (on/off), not per-field. A single column is the
  simplest structure that satisfies FR-004/FR-005 without overbuilding for
  the granular per-field opt-in explicitly deferred as a "reasonable future
  refinement."
- **Alternatives considered**: Separate settings table keyed by person —
  rejected as premature for a single boolean; revisit only if/when granular
  opt-in is actually scoped.

## Decision: automatic lift/reapply via a model observer, not manual controller logic

- **Decision**: Use a Laravel model observer (or a `saving`/`saved` event
  hook on `Person`) that checks whether `dod`/`yod` changed on save, and
  auto-manages a *derived* protection state — not `is_publicly_visible`
  itself, which remains the user's explicit opt-in choice, but the
  *effective* visibility computed by `PersonPrivacy::isPubliclyVisible()`
  (`= isPubliclyVisible column OR isDeceased()`).
- **Rationale**: This distinguishes "the record owner opted in" from "the
  person happens to be deceased" as two independent, non-conflicting
  reasons a profile is public — reversing a death-date correction (Edge
  Cases) then correctly falls back to the opt-in column's value rather than
  needing its own separate "was this auto-set" bookkeeping.
- **Alternatives considered**: Storing a single merged `is_public` boolean
  that gets overwritten when death is recorded — rejected: this would lose
  the user's original opt-in choice if death is later corrected/reversed,
  violating the Edge Cases "symmetric re-protection" requirement in a
  confusing way (a previously non-opted-in person incorrectly ending up
  opted-in after a death-date correction round-trip).

## Decision: `PersonPolicy::togglePrivacy` reuses spec 002's ownership rule

- **Decision**: Who may flip `is_publicly_visible` is governed by the same
  `PersonPolicy` introduced in spec 002 (edit-rights holder on that
  person), extended with one new ability method, not a new authorization
  mechanism.
- **Rationale**: Consistency — "who can edit this person" and "who can
  change this person's visibility" are the same trust boundary in this
  product; no requirement in spec 007 suggests otherwise.
- **Alternatives considered**: A distinct "privacy officer" role — no
  requirement supports this; not introduced without a concrete need.
