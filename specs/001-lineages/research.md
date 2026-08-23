# Phase 0 Research: Lineages

No `NEEDS CLARIFICATION` markers remain in the spec, and the technology
stack is fixed by Constitution Principles III/IV — there are no open
technology choices to research. This file records the small number of
implementation-pattern decisions made while reading the existing codebase
during the 000 audit.

## Decision: Pivot table, not a nullable FK

- **Decision**: Model lineage membership as a `lineage_person` pivot table
  (`lineage_id`, `person_id`, timestamps, unique composite key), not as a
  nullable `lineage_id` column on `people`.
- **Rationale**: A single-column FK would reproduce the exact `team_id`
  problem identified in the 000 audit (one person → one lineage). The whole
  point of this feature is N:N.
- **Alternatives considered**: JSON column of lineage IDs on `people` —
  rejected: not queryable/indexable the way a real pivot is, and Laravel's
  `belongsToMany` already gives idiomatic attach/detach/sync semantics for
  free.

## Decision: `LineageMembership` gets its own thin model, not just a pivot array

- **Decision**: Define an explicit `LineageMembership` Eloquent model over
  the pivot table (via `->using()`), even though spec 001 keeps membership
  untyped for MVP (no "by birth" vs "by marriage" distinction yet).
- **Rationale**: Spec 001's Assumptions section explicitly flags typed
  membership as a likely near-term follow-up. Starting with a real pivot
  model (not just `belongsToMany` array pivots) avoids a breaking migration
  later when a `type` column is added.
- **Alternatives considered**: Plain `belongsToMany` with array pivot
  columns — rejected for the reason above; the cost of a dedicated model
  class is near zero.

## Decision: Reuse `LogsActivity` pattern already on `Person`/`Couple`/`Team`

- **Decision**: `Lineage` uses the same `Spatie\Activitylog` `LogsActivity`
  trait and `getActivitylogOptions()` pattern already established.
- **Rationale**: Constitution doesn't mandate this, but consistency with
  every other domain model in the codebase (per CLAUDE.md: "follow existing
  code conventions") outweighs any argument for a different approach.
- **Alternatives considered**: No activity log on Lineage — rejected, would
  create an inconsistent auditability gap versus every other entity.
