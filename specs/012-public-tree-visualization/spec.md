# Feature Specification: Public Tree Visualization

**Feature Branch**: `012-public-tree-visualization`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Modernize the public (non-admin) frontend, especially the multi-generation genealogy tree presentation, reusing the existing site theme. Stay on the current Laravel/Livewire stack; import a compatible, complete, modern graphics library for the tree rendering only — not the back-office/management screens."

## Clarifications

### Session 2026-08-23

- Q: Dans l'arbre graphique, les couples (conjoints/partenaires) doivent-ils apparaître comme des paires reliées, ou seulement la ligne de filiation directe ? → A: Paires reliées — chaque personne et son/ses partenaire(s) apparaissent côte à côte, reliés par un trait d'union, enfants sous le couple (mode natif de family-chart, cohérent avec le modèle `Couple` déjà en base, remariages inclus).
- Q: Les cartes de personne doivent-elles afficher la photo (quand elle existe), ou rester texte seul ? → A: Avec photo si disponible (Spatie MediaLibrary), silhouette par défaut sinon.
- Q: Comment un visiteur ouvre-t-il la fiche d'une personne depuis un nœud, sans conflit avec le glisser du canvas ? → A: Clic simple (sans glissement) sur la carte — comportement natif de family-chart, pas de bouton dédié.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Explore a modern, interactive descendant tree (Priority: P1)

A visitor on a person's public profile opens "Descendants" (spec 004) and now
sees an interactive, pannable/zoomable graphical tree instead of today's
static nested HTML blocks, styled consistently with the rest of the site.

**Why this priority**: The descendant tree is the platform's flagship
browsing experience (product vision F05); today it is functional but visually
static (plain recursive Blade partials, no pan/zoom/graphical layout) — this
is the single highest-impact visual upgrade available.

**Independent Test**: Open the descendant tree for a person with 3+
generations of recorded descendants and verify the visitor can pan and zoom
the canvas, expand/collapse branches, and every node still links to that
person's profile — with zero change to which descendants are shown.

**Acceptance Scenarios**:

1. **Given** a person with children and grandchildren recorded, **When** a
   visitor opens the descendant tree, **Then** it renders as a graphical,
   zoomable/pannable tree rooted at that person, visually matching the site's
   existing theme (colors, typography, dark/light mode).
2. **Given** a collapsed branch, **When** a visitor expands it, **Then** the
   next generation loads progressively (reusing spec 004's existing lazy-load
   behavior) without a full page reload.
3. **Given** a node in the tree, **When** a visitor clicks it, **Then** they
   navigate to that person's profile (spec 003), unchanged from today.

---

### User Story 2 - Explore a modern, interactive ancestor tree (Priority: P1)

The same visual and interaction upgrade applied to the ancestor tree (spec
005), so both tree directions feel like one consistent product.

**Why this priority**: Ancestors and descendants are the two symmetric core
browsing surfaces (F05); shipping only one would leave a visibly
inconsistent public experience.

**Independent Test**: Open the ancestor tree for a person with 3+ known
generations of ancestors and verify the same pan/zoom/expand behavior and
visual consistency as User Story 1.

**Acceptance Scenarios**:

1. **Given** a person with recorded parents and grandparents, **When** a
   visitor opens the ancestor tree, **Then** it renders with the same
   graphical renderer and visual language as the descendant tree.
2. **Given** the ancestor tree, **When** a visitor pans or zooms, **Then**
   the interaction feels identical to the descendant tree (same controls,
   same gestures).

---

### User Story 3 - Use the tree comfortably on a phone (Priority: P2)

A visitor on a smartphone opens either tree and can read, pan, and zoom it
with touch gestures, without horizontal page scroll or broken layout.

**Why this priority**: The product's stated priority order is
smartphone-first; a graphical tree that only works with a mouse would
regress the current (already responsive, if visually plain) Blade version.

**Independent Test**: Open a descendant or ancestor tree on a mobile-width
viewport and verify touch pan/zoom works and no element overflows the
viewport horizontally.

**Acceptance Scenarios**:

1. **Given** a mobile viewport, **When** a visitor opens a tree, **Then** it
   fits the screen width with no horizontal page scroll, and pinch-to-zoom /
   drag-to-pan work on the canvas itself.

---

### Edge Cases

- What happens when a person has zero recorded descendants/ancestors? → The
  canvas shows the person alone with the existing "no descendants/ancestors
  recorded" message (unchanged from specs 004/005), not an empty or broken
  canvas.
- What happens with a living person node? → Rendered with the same
  privacy-limited treatment as today (spec 007's badge/reduced detail),
  re-skinned for the new renderer but not weakened.
- What happens with a very wide generation (many siblings/partners at one
  level)? → The canvas MUST remain pannable/zoomable rather than forcing an
  unreadably compressed layout; horizontal scroll is expected and acceptable
  *inside the canvas*, not on the page itself.
- What happens if a visitor's browser has JavaScript disabled? → Out of
  scope for this spec (see Assumptions); the existing server-rendered tree
  views are not required to keep working without JS after this upgrade.
- What happens on a cross-lineage branch (spec 002)? → Renders inline with
  no visual seam, same as today's requirement in specs 004/005.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST render the descendant tree (spec 004) and the
  ancestor tree (spec 005) as an interactive graphical visualization
  (pan, zoom, expand/collapse) instead of static nested HTML.
- **FR-002**: The visual style of tree nodes and the canvas (colors,
  typography, spacing, dark/light mode) MUST reuse the site's existing
  Tailwind/TallStackUI design tokens — no separate design system introduced.
- **FR-009**: Couples MUST render as a connected pair (both partners side by
  side, joined by a union line), with their children attached below the
  couple — reflecting the existing `Couple` model, including a person shown
  paired with more than one partner across remarriages where recorded.
- **FR-010**: Each node MUST display the person's photo when one exists
  (existing Spatie MediaLibrary attachment), falling back to a placeholder
  silhouette when none is set.
- **FR-011**: A single click/tap on a node card (not preceded by a drag
  gesture) MUST navigate to that person's profile (spec 003); dragging the
  canvas or a node MUST NOT trigger navigation.
- **FR-003**: Progressive/lazy loading of additional generations on branch
  expansion (spec 004 FR-002, spec 005 equivalent) MUST be preserved
  unchanged in substance — only its visual presentation changes.
- **FR-004**: Living-person nodes MUST continue to render with the existing
  privacy-limited treatment (spec 007), adapted visually to the new
  renderer — including suppressing the photo (FR-010) whenever spec 007
  already withholds that person's identifying details.
- **FR-005**: Every node MUST remain a link to that person's public profile
  (spec 003), unchanged.
- **FR-006**: The tree MUST be usable via touch (pan, zoom) on mobile
  viewports, with no horizontal overflow of the page itself.
- **FR-007**: This spec covers only public-facing surfaces reachable from a
  person's public profile (descendant/ancestor trees). Back-office,
  moderation, and developer screens are explicitly out of scope and MUST NOT
  be modified by this feature.
- **FR-008**: No new backend traversal/query logic is introduced; the
  existing descendant/ancestor data already produced by specs 004/005's
  Livewire components is reused as-is (Constitution Principle VI — no CTE
  rewrite).

### Key Entities

- **Person**: unchanged; this spec is a presentation-layer upgrade only.
- **Couple**: unchanged; rendered visually as a paired node (FR-009) using
  the existing `person1_id`/`person2_id` relationship, including a person
  who appears in more than one recorded couple (remarriage).
- **Tree node (view-model)**: not persisted — assembled at render time from
  the existing descendant/ancestor payloads already produced by specs
  004/005 (name, lifespan, photo URL, privacy flag, generation,
  parent/child/partner references).

### Team/Lineage scope interaction *(Constitution Principle VI & VII)*

No change. This spec consumes the same team/lineage-crossing descendant and
ancestor payloads already produced by specs 002/004/005; it does not touch
the `team` global scope or the recursive query engine.

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- No new routes. Reuses the existing public routes from specs 004/005
  (`p/{person}/descendants`, `p/{person}/ancestors`) unchanged.

### Livewire Components

- `Livewire/People/Descendants/Tree.php` and
  `Livewire/People/Ancestors/Tree.php` are updated to serialize their
  existing tree payload as JSON for client-side rendering, instead of (or in
  addition to, for the initial no-JS paint) recursively rendering
  `tree-node.blade.php` partials.
- A new shared Blade component (e.g.
  `resources/views/components/family-tree-canvas.blade.php`) wraps a
  `wire:ignore` container that an Alpine.js component hydrates with the
  chosen graphical library, reused identically by both the descendant and
  ancestor trees to avoid duplicating initialization logic.
- Branch expand/collapse and lazy-load actions continue to be dispatched as
  Livewire actions (per spec 004 FR-002); the client-side renderer reflects
  the updated payload after each Livewire response.

### Key Screen States

- **Initial load**: canvas shows a lightweight skeleton/placeholder while
  the first payload streams in, then renders the tree.
- **Branch loading**: expanding a branch shows a small localized
  spinner/skeleton on just that node/branch — the rest of the canvas stays
  interactive, matching spec 004's existing localized-loading requirement.
- **Empty state**: root person alone with the existing "no
  descendants/ancestors recorded" message.
- **Living-person node**: existing privacy badge / reduced-detail styling,
  ported to the new node renderer, photo suppressed (FR-004/FR-010).
- **Couple node**: two partner cards joined by a union line, children
  attached below the pair (FR-009); a remarried person appears once per
  recorded couple, each pairing rendered independently.
- **Mobile/compact**: nodes may show a reduced card (avatar + name only)
  below a viewport-width threshold, with full detail available on tap —
  exact threshold decided during implementation.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Visitors can pan and zoom the descendant and ancestor trees
  smoothly across at least 3 generations without a full page reload at any
  step.
- **SC-002**: Tree nodes are visually indistinguishable in look-and-feel
  (colors, fonts, spacing, dark/light mode) from the rest of the public
  site's existing theme.
- **SC-003**: A descendant or ancestor tree opened on a mid-range mobile
  device remains usable — touch pan/zoom works, no horizontal page overflow.
- **SC-004**: No back-office, moderation, or developer screen is modified by
  this feature (verifiable by diff review scoped to the public tree
  components and shared public UI assets only).

## Assumptions

- The rendering library is already agreed with the project owner:
  **family-chart** — a framework-agnostic (vanilla JS/SVG), MIT-licensed
  library purpose-built for genealogical trees, added as a single new npm
  dependency (approved per the project's dependency-approval convention).
- This spec supersedes the "rendering technology intentionally not
  specified" assumption left open in specs 004 and 005 — their functional,
  privacy, and traversal requirements are unchanged; only the rendering
  layer is upgraded here.
- Existing Tailwind/TallStackUI theme tokens (colors, dark-mode variables)
  are reused as-is; no new design system, rebrand, or typography change.
- Desktop and mobile web only; no native app wrapping (matches the product
  cadrage's excluded scope).
- Admin/back-office/moderation/developer areas are explicitly untouched by
  this spec (FR-007).
- A visitor with JavaScript disabled is not a supported scenario for the
  graphical tree after this upgrade (see Edge Cases); no server-rendered
  fallback tree is required to be maintained in parallel.
