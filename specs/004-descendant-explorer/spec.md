# Feature Specification: Descendant Explorer

**Feature Branch**: `004-descendant-explorer`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Starting from any person, let a visitor explore their descendants across generations, as an interactive tree and as a filterable list, reusing the existing recursive descendant queries, without loading the whole database at once."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visualize descendants as an interactive tree (Priority: P1)

A visitor on a person's profile opens "Descendants" and sees a tree starting
at that person, showing children, grandchildren, and so on, with branches
they can open and close.

**Why this priority**: This is the flagship browsing experience described in
the product vision (F05) and the most-demonstrated feature of any genealogy
product — it's the clearest proof that spec 001/002 (cross-lineage graph)
delivers real value.

**Independent Test**: Open the descendant tree for a person with 3+
generations of recorded descendants and verify each generation can be
expanded/collapsed and every visible node links to that person's profile.

**Acceptance Scenarios**:

1. **Given** a person with children and grandchildren recorded, **When** a
   visitor opens the descendant tree, **Then** the person appears as the
   root, with their children as the first level, collapsed by default beyond
   a reasonable initial depth.
2. **Given** a collapsed branch, **When** a visitor clicks to expand it,
   **Then** the next generation loads and displays without reloading the
   whole page.
3. **Given** a node in the tree, **When** a visitor clicks it, **Then** they
   navigate to that person's profile (spec 003).

---

### User Story 2 - View descendants as a filterable list (Priority: P2)

A visitor switches from the tree view to a flat list of the same descendants,
showing generation number, name, and lineage, and can filter by generation or
name.

**Why this priority**: Complements the tree for cases where a graphical tree
is hard to scan (very wide trees, or users who want to search/sort) — product
vision F06.

**Independent Test**: Open the list view for the same person as User Story 1
and verify the same set of descendants appears, filterable by generation.

**Acceptance Scenarios**:

1. **Given** the same descendant set as the tree view, **When** a visitor
   switches to list view, **Then** every descendant shown in the tree appears
   in the list with a generation number.
2. **Given** the list view, **When** a visitor filters by a specific
   generation, **Then** only descendants at that generation are shown.

---

### User Story 3 - Choose how many generations to explore (Priority: P3)

A visitor sets a maximum number of generations to load, to keep very large
families manageable.

**Why this priority**: Performance/usability safeguard for large families;
not needed to demonstrate the core value, but needed before this is exposed
to real public data at scale.

**Independent Test**: Set generation limit to 2 for a person with 5 known
generations of descendants and verify only 2 generations are returned.

**Acceptance Scenarios**:

1. **Given** a person with 5 generations of descendants, **When** a visitor
   sets the generation limit to 2, **Then** only 2 generations are shown, with
   an indicator that more exist.

---

### Edge Cases

- What happens when a person has zero recorded descendants? → The tree/list
  shows the person alone with a clear "no descendants recorded" state, not an
  error.
- What happens when a descendant is a living person? → They appear in the
  tree/list per the privacy rule (spec 007): visible as a node, but without
  private details, consistent with spec 003 profile behavior.
- What happens when the descendant graph includes a cycle due to a data-entry
  error (e.g. a person mistakenly recorded as their own ancestor)? → The
  traversal MUST terminate rather than loop indefinitely; the existing
  recursive query engine's depth handling is the mechanism relied upon here
  (see Team/Lineage scope interaction note).
- What happens when descendants span multiple lineages/teams (spec 002)? →
  They MUST still appear in the same traversal — this is the entire point of
  spec 002.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST render an interactive descendant tree rooted at any
  given person, reachable from that person's public profile.
- **FR-002**: System MUST load descendant branches progressively (on
  expand), not the entire descendant set in one response, for people with
  large descendant counts.
- **FR-003**: System MUST offer an equivalent flat list view of the same
  descendant set, with generation number shown per person.
- **FR-004**: System MUST allow filtering the list view by generation.
- **FR-005**: System MUST allow the visitor to bound the traversal to a
  maximum number of generations.
- **FR-006**: System MUST apply the same privacy rule to descendant nodes as
  to standalone profiles (living people shown without private details).
- **FR-007**: System MUST traverse descendants across lineage/team boundaries
  wherever a recorded relationship (couple or parent/child) connects them, per
  spec 002.
- **FR-008**: System MUST link every node in the tree or list to that
  person's own profile (spec 003).

### Key Entities

- **Person**: unchanged; this spec is a read/traversal feature only.
- **Descendant relationship**: derived, not stored — computed via existing
  `father_id`/`mother_id`/`parents_id` links and the existing recursive query
  engine (`app/Queries/*DescendantsQuery.php`).

### Team/Lineage scope interaction *(Constitution Principle VI & VII)*

This spec reuses `app/Queries/{MySql,PgSql,SQLite}DescendantsQuery.php`
unchanged in their core recursive logic (Principle VI). Their existing team
scoping/parameters MUST be adapted so traversal does not stop at a team
boundary when a genealogical relationship crosses it (consistent with spec
002 FR-002). This is a query-parameter adaptation, not a rewrite of the CTE
itself.

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- `GET /people/{person}/descendants` — becomes a **public** route (the
  underlying route already exists per the 000 audit but is currently behind
  `auth:sanctum`; this spec makes the public variant available, reusing the
  same view/component shell where possible).

### Livewire Components

- `DescendantExplorer` — top-level component hosting a Tree/List tab switch
  (User Story 1 vs. 2), a generation-limit control (User Story 3, numeric
  stepper or dropdown), and the shared root-person header (name, photo,
  reused from `PersonProfile`).
- `DescendantTree` — the interactive tree (zoom/pan container); each node is
  a clickable card (name, lifespan, privacy badge if applicable) linking to
  that person's profile (spec 003); collapsed branches show an expand
  affordance (chevron/+) that triggers a Livewire action to lazy-load the
  next generation (FR-002) with a small inline loading spinner on that
  branch only — not a full-page loader.
- `DescendantList` — TallStackUI table: columns Generation / Name / Lineage /
  Birth year; a generation filter dropdown (FR-004) and a name filter input.

### Key Screen States

- **Root only, no descendants**: tree/list shows the root person card alone
  plus "Aucun descendant enregistré."
- **Branch loading**: expand-in-progress shows a localized spinner/skeleton
  on just that branch, tree remains interactive elsewhere.
- **Generation limit reached**: last visible generation row/level shows a
  "D'autres générations existent — augmenter la limite" indicator (User
  Story 3) rather than silently truncating with no explanation.
- **Living descendant node**: rendered with the shared `PrivacyBanner`-style
  badge (spec 003/007) instead of full details.
- **Cross-lineage node**: per spec 002, a node reached via a different team's
  data renders inline, same visual treatment as any other node — no seam.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A visitor can open a descendant tree and expand three levels of
  branches without a full page reload at any step.
- **SC-002**: Descendant trees crossing two or more lineages display as one
  connected tree, with zero manual steps to "merge" the view.
- **SC-003**: Opening a descendant tree for a person with 200+ recorded
  descendants remains usable (progressive loading prevents a single
  all-at-once render).

## Assumptions

- Default initial expansion depth (how many generations show before the
  visitor must click to expand) is a UX default, not specified numerically
  here; a reasonable default (e.g. 2–3 generations) is chosen during
  implementation.
- The specific charting/rendering technology for the tree is intentionally
  not specified (business-behavior spec); Constitution Principle III already
  fixes the stack to Laravel/Livewire for the MVP.
- "Descendant" follows the existing data model's definition (natural
  children via father_id/mother_id/parents_id); adoption/other filiation
  types remain a documented gap per the 000 audit, not solved by this spec.
