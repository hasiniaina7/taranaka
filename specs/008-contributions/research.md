# Phase 0 Research: Contributions and Moderation

## Decision: a dedicated `Contribution` model/table, not reuse of Activitylog

- **Decision**: New `contributions` table, separate from
  `Spatie\Activitylog`'s `activity_log` table already attached to `Person`/
  `Couple`/`Team`.
- **Rationale**: Activitylog records *what already happened* (post-hoc,
  descriptive). A Contribution records *what is being requested* and must
  support a pending state that has NOT yet happened to the target record —
  a fundamentally different lifecycle (draft → decided) that Activitylog's
  model doesn't represent. Forcing this into Activitylog would mean
  inventing a fake "pending" log entry type, which is a worse fit than a
  purpose-built table.
- **Alternatives considered**: Extending Activitylog's `properties` JSON
  column with a custom status — rejected, conflates two different concerns
  and would make Activitylog's own simple "what changed and when" queries
  (already relied on elsewhere) harder to reason about.

## Decision: generic `target_type`/`target_id`, not one table per contribution kind

- **Decision**: `Contribution` uses a polymorphic-style `target_type` +
  `target_id` (nullable for "new person" proposals) rather than separate
  `PersonContribution`/`RelationshipContribution` tables.
- **Rationale**: FR-001/FR-008 both list "correction," "new person," and
  "new relationship" as contribution kinds sharing the same review
  lifecycle (queue, accept, reject, traceability). A single table with an
  `action` discriminator column keeps the moderator queue (FR-004) a single
  query instead of a union across multiple tables.
- **Alternatives considered**: Per-kind tables — rejected, would fragment
  `ModerationQueue`'s single-list requirement (FR-004) into a manual merge
  of multiple sources.

## Decision: applying an accepted Contribution goes through normal Eloquent save

- **Decision**: `ContributionReview`'s accept action calls the same
  `Person`/`Couple` update path a direct edit would use (not a raw DB
  write), so the existing `LogsActivity` trait naturally records the
  applied change in Activitylog too.
- **Rationale**: Keeps a single source of truth for "what changed on this
  record," with `Contribution` additionally capturing "who proposed it and
  why" — the two logs stay consistent without duplicated write logic.
- **Alternatives considered**: A separate "apply" service bypassing normal
  model events — rejected, would silently break Activitylog coverage for
  contribution-originated changes.
