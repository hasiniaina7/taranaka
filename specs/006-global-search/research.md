# Phase 0 Research: Global Search

## Decision: explicit `withoutGlobalScope('team')`, not reliance on guest absence of scope

- **Decision**: `SearchController` explicitly calls
  `Person::withoutGlobalScope('team')->search(...)` rather than relying on
  the fact that a guest has no `currentTeam` (which today already causes
  the scope closure to no-op).
- **Rationale**: spec 002's Edge Cases explicitly warns against this kind
  of implicit/accidental scoping — an authenticated contributor using
  search should get the same cross-team results as a guest, and relying on
  "no current team" as the mechanism would silently break search the
  moment an authenticated user runs it (their `currentTeam` is set, so the
  scope would silently re-apply and under-return results). Explicit bypass
  is correct for both guest and authenticated callers.
- **Alternatives considered**: Rely on guest state — rejected per above,
  fails the moment a logged-in contributor uses the same search box.

## Decision: reuse `Person::scopeSearch()` verbatim, add `Lineage::scopeSearch()` as a new, simpler sibling

- **Decision**: No change to `Person::scopeSearch()`'s implementation
  (multi-word CSV parsing, wildcard prefix handling, LIKE escaping already
  correct per the 000 audit). `Lineage::scopeSearch()` is new but
  intentionally simpler (single-field `name` LIKE with the same
  `$escapeLike` pattern copy) — lineage names don't need the multi-word/
  quoted-phrase sophistication person names do.
- **Rationale**: Minimizes new surface area; the existing person search is
  already correct and battle-tested by existing usage in the authenticated
  app.
- **Alternatives considered**: A single generic polymorphic search scope
  covering both models — rejected as premature abstraction for two call
  sites with meaningfully different matching needs (Person: multi-field,
  multi-word; Lineage: single-field).

## Decision: no dedicated search engine for MVP

- **Decision**: Confirmed per Constitution Principle IV and the product
  cadrage document (§3.8) — MySQL LIKE-based search only.
- **Rationale**: Already a ratified constraint; restated here so the plan
  doesn't reopen it.
