# Phase 0 Research: Descendant Explorer

## Finding: the existing recursive query is already cross-team

- **Observation**: `app/Queries/MySqlDescendantsQuery::getRecursiveQuery()`
  selects from `people` filtered only by `deleted_at IS NULL` and the
  father/mother join — there is no `team_id` predicate anywhere in the CTE.
  It runs via `DB::select()` (raw query), which bypasses Eloquent global
  scopes entirely.
- **Implication**: spec 002's FR-002 ("descendant/ancestor traversal MUST
  cross team boundaries") requires **no change** to this query. The only
  risk is a *future* developer adding a team filter to "fix" what looks
  like a missing scope — this plan's test suite includes an explicit
  regression test asserting cross-team results, specifically to prevent
  that.
- **Action**: none needed on the query itself; documented here so the
  implementation phase doesn't waste effort re-deriving this.

## Decision: public route lives at `/p/{person}/descendants`, not `/people/{person}/descendants`

- **Decision**: The new public route uses the `/p/` prefix established by
  spec 003 (`/p/{person}`), giving `GET /p/{person}/descendants`, instead of
  reusing the literal `people/{person}/descendants` path.
- **Rationale**: `routes/web.php` already registers
  `Route::get('people/{person}/descendants', 'descendants')->name('people.descendants')`
  inside the `auth:sanctum` group. Laravel resolves a duplicate URI+method
  pair by registration order, not by route name — adding a second route at
  the exact same `people/{person}/descendants` path (even named
  differently, e.g. `front.people.descendants`) would leave one of the two
  permanently unreachable, silently breaking either the guest explorer or
  the existing authenticated one depending on registration order. Sharing
  the `/p/` prefix with spec 003 also keeps all public person-scoped routes
  under one predictable namespace.
- **Alternatives considered**: Registering the public route with a
  different *name* but the same URI — rejected, doesn't avoid the
  collision (Laravel routes by URI+method, not by name). Removing/renaming
  the existing authenticated route — rejected, out of scope and would break
  existing authenticated-user bookmarks/links for no benefit.

## Decision: UI-level progressive reveal, not server-side lazy branch loading

- **Decision**: Fetch the full depth-bounded descendant set in one request
  (existing query behavior, bounded by `$maxDepth`), then reveal branches
  client-side on expand — not a separate server round-trip per branch
  expansion.
- **Rationale**: The existing query already returns a bounded, flat result
  set with a `degree` column in one pass; splitting that into per-branch
  lazy server calls would be strictly slower (N round-trips instead of 1)
  for the depths this feature targets (spec 004 Success Criteria: 3 levels
  expanded smoothly). True server-side lazy loading only pays off at depths
  well beyond what a person's browser-rendered tree can usefully display
  anyway.
- **Alternatives considered**: Livewire `wire:click` triggering a fresh
  server query per branch — rejected for the performance reason above;
  reconsidered only if a future need for very deep (50+ generation) trees
  emerges, which is out of scope for MVP.

## Decision: Tree rendering approach

- **Decision**: Render the tree as nested Blade partials driven by
  Livewire component state (expanded/collapsed node IDs tracked in a
  Livewire public property), styled with Tailwind, no third-party JS
  charting library.
- **Rationale**: Constitution Principle III fixes Livewire/TallStackUI for
  the MVP; the product cadrage document's mention of D3.js/family-chart was
  explicitly scoped to a *deferred* Next.js frontend option, not the MVP.
  A dependency-free Blade/Alpine (already bundled with Livewire) approach
  keeps this feature inside the existing stack with zero new dependencies
  (no approval needed per CLAUDE.md's "don't change dependencies without
  approval").
- **Alternatives considered**: Introduce a JS charting library (D3,
  family-chart) — rejected for MVP per Constitution Principle III; may be
  revisited only if a future spec explicitly re-opens that decision.
