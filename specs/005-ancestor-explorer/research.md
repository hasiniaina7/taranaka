# Phase 0 Research: Ancestor Explorer

## Finding: verify the ancestor CTE is also team-filter-free

- **Action for implementation phase**: confirm
  `app/Queries/MySqlAncestorsQuery.php` follows the same pattern already
  confirmed for `MySqlDescendantsQuery` in spec 004's research.md (no
  `team_id` predicate in the recursive CTE, raw `DB::select()` bypassing
  Eloquent scopes). Given both were written together in the same codebase
  by the same convention, this is expected but must be verified, not
  assumed, before relying on it in the cross-team traversal test.
- **If the finding differs** (e.g. the ancestor query does filter by team
  where the descendant one doesn't): treat this as a research surprise, not
  a plan change — document the actual behavior in this file during
  implementation and adjust FR-008 (cross-team ancestor traversal) approach
  accordingly, still without violating Constitution Principle VI (adapt
  parameters, don't rewrite the CTE core).

## Decision: reuse spec 004's UI shell and rendering decisions

- **Decision**: `AncestorExplorer`/`AncestorTree`/`AncestorList` follow the
  exact same architectural choices as spec 004 (UI-level progressive
  reveal, Blade/Alpine rendering, no new JS dependency).
- **Rationale**: spec 005's own Assumptions section already states the two
  explorers are expected to share the bulk of their implementation;
  duplicating the decision-making here would be redundant — see spec 004's
  research.md for the full rationale.

## Decision: "unknown parent" placeholder is a rendering concern, not a data concern

- **Decision**: A missing father/mother is represented in the UI layer as a
  placeholder card (FR-006), not by inserting a synthetic "Unknown Person"
  row into the query result set.
- **Rationale**: Keeps the query contract (`AncestorsQueryInterface`)
  identical in shape to the descendants one and avoids polluting the
  `people` table's semantics with placeholder records. The tree-building
  transform (in `AncestorTree`) simply renders an empty slot wherever
  `father_id`/`mother_id` is null for a node that would otherwise have one.
- **Alternatives considered**: Synthetic placeholder `Person` rows in the
  database — rejected, violates Constitution Principle I's spirit (a
  placeholder is not "one person, one entity," it's a UI artifact wearing a
  data-model costume).
