# Phase 0 Research: Global Genealogy Model

## Decision: Refine the global scope's predicate, don't remove it

- **Decision**: `Person::booted()`/`Couple::booted()` keep a global scope,
  but its `WHERE` clause changes from a strict `team_id = current_team`
  filter to `team_id = current_team OR id IN (<reachable via relationship
  from something already in current_team>)`.
- **Rationale**: Removing the scope entirely would silently expose every
  team's private data to every other team's contributors on every existing
  list/search view — a severe regression (violates User Story 2's
  zero-regression requirement). Keeping the scope but widening its predicate
  preserves today's behavior for isolated data while unblocking connected
  data, exactly per FR-003.
- **Alternatives considered**: Application-level filtering (compute
  visibility in PHP after fetching) — rejected: defeats the purpose of a
  database-level scope, reintroduces N+1 risk, and every future new query
  would need to remember to apply it manually.

## Decision: A dedicated `PersonPolicy` for the FR-004 ownership rule

- **Decision**: Centralize "who may edit this person" (FR-004) in one
  Laravel Policy class, reused by every controller/Livewire component that
  currently checks permissions ad hoc.
- **Rationale**: Constitution Principle VII requires every spec touching
  `Person`/`Couple` to state its team-scope interaction explicitly. A single
  Policy is the only way to guarantee FR-004 is enforced identically
  everywhere, rather than re-derived per screen (which is exactly how the
  kind of security drift the constitution is trying to prevent happens).
- **Alternatives considered**: Per-controller manual checks (the pattern
  used loosely today, e.g. `Team::isDeletable()`'s inline
  `auth()->user()?->isDeveloper()` checks) — rejected for new code going
  forward; existing inline checks are left alone (not in scope to refactor
  here) but are not the pattern to extend.

## Decision: "Reachable" (FR-003) means one-hop relationship, not transitive closure

- **Decision**: The widened visibility predicate covers people/couples
  directly connected (as a couple partner, parent, or child) to something
  already in the viewer's team — not an unbounded transitive walk across
  the whole graph.
- **Rationale**: An unbounded transitive walk would, over time, make nearly
  every person "reachable" from nearly every team once enough marriages
  connect lineages — defeating the purpose of team-scoped list/search views
  entirely. A one-hop rule keeps today's list/search views close to their
  current size while still unblocking the descendant/ancestor explorers
  (specs 004/005), which perform their own multi-hop traversal via the
  recursive query engine, not via this scope.
- **Alternatives considered**: Full transitive reachability via the global
  scope itself — rejected per above; the recursive CTE queries (Principle
  VI) are the correct place for multi-hop traversal, not the default list
  scope.
