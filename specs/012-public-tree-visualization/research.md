# Research: Public Tree Visualization

## Decision: Rendering library — `family-chart`

**Decision**: Use the `family-chart` npm package (vanilla JS/SVG, MIT
license, no framework dependency) as the client-side renderer for both the
descendant tree (spec 004) and the ancestor tree (spec 005).

**Rationale**: Approved directly with the project owner during spec
clarification. It is purpose-built for genealogical trees (native
couple-pairing, pan/zoom, expand/collapse), requires no SPA framework
(fits Constitution Principle III — Laravel/Livewire stays the MVP stack),
and integrates as a single Vite-bundled ES module hydrated inside a
`wire:ignore` container.

**Alternatives considered**:
- Raw D3.js — more flexible, but requires hand-building tree layout,
  couple-pairing, and touch gesture handling from scratch; strictly more
  implementation risk for no product benefit here.
- BALKANgraph FamilyTree.js — visually rich, but its license requires a
  paid commercial license once the site is public/commercial — rejected
  per the project owner's decision to avoid unbudgeted license risk.

## Decision: Two independent Livewire payload shapes stay independent

**Decision**: Do not unify `Descendants\Tree` and `Ancestors\Tree` into one
shared payload builder as part of this spec. Each keeps producing its own
JSON shape (see data-model.md); a thin per-tree adapter maps each shape into
the one `family-chart` input format the shared canvas component expects.

**Rationale**: The two components already have materially different
loading strategies — `Descendants\Tree` computes a full depth-bounded tree
in one `#[Computed(persist: true)]` call via `BuildDescendantNodes`
(sequence/parent_sequence based), while `Ancestors\Tree` lazily loads one
branch per Livewire round-trip (`father_id`/`mother_id` based, per-node
`loadBranch()`). Forcing a shared payload builder would mean rewriting one
of the two existing, already-tested traversal strategies — a bigger,
riskier change than this spec's scope (visual/rendering upgrade only,
Constitution Principle VI: no rewrite of the underlying
traversal/query logic). Unifying them is a candidate for a future spec, not
this one.

## Decision: Couple pairing is assembled client-side from existing relations

**Decision**: Both payload builders are extended to include a `partner_ids`
array per node (from `Person::couples()`, already a `HasManyMerged` over
`person1_id`/`person2_id`), plus one `couples` list (pairs + their shared
children) consumed by the adapter that builds `family-chart`'s input. No new
Eloquent relation or migration is needed — `Couple` already models this.

**Rationale**: FR-009 (clarified) requires couples to render as paired
nodes. `family-chart`'s native data format already expects couple pairing
(`rels.spouses`); the existing `Person::couples()` relation supplies exactly
this without any new query.

## Decision: Photo URL resolution reuses the existing legacy storage path

**Decision**: Photo URLs for tree nodes are resolved the same way the
existing (non-public) `x-tree-node.*` Blade components already do it:
`Storage::disk('photos')->url("{team_id}/{person_id}/{photo}_small.webp")`,
falling back to a placeholder silhouette asset when `person->photo` is null
or the file does not exist on disk.

**Rationale**: This is already the working, tested mechanism elsewhere in
the codebase (`resources/views/components/tree-node/{ancestors,
descendants}.blade.php`) — reusing it avoids introducing a second photo-URL
convention (Constitution Principle VI's "don't rewrite what already works"
spirit, applied to this adjacent concern).

## Decision: Click vs. drag disambiguation is handled by `family-chart` itself

**Decision**: Rely on `family-chart`'s built-in click event (fired only when
a pointer-down/up pair on a card completes without exceeding its internal
drag threshold), wired to `window.location` navigation to the person's
public profile URL embedded in each node's data.

**Rationale**: Matches the clarified FR-011 behavior (single click/tap
navigates, drag does not) without custom gesture-detection code — the
library already solves this for its own pan/drag interactions.
