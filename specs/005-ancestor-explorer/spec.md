# Feature Specification: Ancestor Explorer

**Feature Branch**: `005-ancestor-explorer`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Starting from any person, let a visitor explore their ancestors across generations (parents, grandparents, ...), mirroring the descendant explorer, reusing the existing recursive ancestor queries."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visualize ancestors as an interactive tree (Priority: P1)

A visitor on a person's profile opens "Ancestors" and sees a tree starting at
that person, showing parents, grandparents, and so on, with branches they can
open and close.

**Why this priority**: Completes the two core exploration modes (descendants
+ ancestors) that together make the product's "start from one person,
discover the graph" promise real in both directions.

**Independent Test**: Open the ancestor tree for a person with 3+ generations
of recorded ancestors and verify each generation can be expanded/collapsed
and every visible node links to that person's profile.

**Acceptance Scenarios**:

1. **Given** a person with two parents and four grandparents recorded,
   **When** a visitor opens the ancestor tree, **Then** the person appears as
   the root, with parents as the first level.
2. **Given** a collapsed branch, **When** a visitor expands it, **Then** the
   next generation of ancestors loads without a full page reload.
3. **Given** a node in the tree, **When** a visitor clicks it, **Then** they
   navigate to that ancestor's profile (spec 003).

---

### User Story 2 - View ancestors as a filterable list (Priority: P2)

A visitor switches to a flat list of the same ancestors, with generation
number and lineage shown, filterable by generation.

**Why this priority**: Same rationale as the descendant list (spec 004 User
Story 2) — mirrors it for the ancestor direction.

**Independent Test**: Open the list view for the same person as User Story 1
and confirm every ancestor from the tree appears with a correct generation
number.

**Acceptance Scenarios**:

1. **Given** the same ancestor set as the tree view, **When** a visitor
   switches to list view, **Then** every ancestor shown in the tree appears in
   the list with a generation number (1 = parents, 2 = grandparents, ...).
2. **Given** the list view, **When** a visitor filters by generation, **Then**
   only ancestors at that generation are shown.

---

### User Story 3 - Handle missing or incomplete ancestor data gracefully (Priority: P2)

A visitor explores ancestors for a person whose lineage is only partially
recorded (e.g. one parent known, the other unknown).

**Why this priority**: Incomplete ancestor data is the norm, not the
exception, in genealogical records — the explorer must handle this cleanly
from day one rather than as an afterthought.

**Independent Test**: Open the ancestor tree for a person with only one
recorded parent and confirm the missing parent is shown as an explicit
"unknown" slot, not silently omitted or shown as an error.

**Acceptance Scenarios**:

1. **Given** a person with only a mother recorded, **When** a visitor opens
   the ancestor tree, **Then** the father slot is shown as "unknown" rather
   than being absent or breaking the layout.

---

### Edge Cases

- What happens when a person has zero recorded ancestors? → The tree/list
  shows the person alone with a "no ancestors recorded" state, not an error.
- What happens when an ancestor is a living person (rare, but possible for
  young root persons)? → Same privacy rule as spec 003/004: visible as a
  node, private details withheld.
- What happens when ancestor data forms a cycle due to a data-entry error? →
  Traversal MUST terminate; same guarantee as the descendant explorer (spec
  004 Edge Cases), from the same underlying recursive query engine.
- What happens when ancestors span multiple lineages/teams (spec 002)? → They
  MUST still appear in the same traversal, same rationale as spec 004 FR-007.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST render an interactive ancestor tree rooted at any
  given person, reachable from that person's public profile.
- **FR-002**: System MUST load ancestor branches progressively (on expand),
  not the entire ancestor set in one response.
- **FR-003**: System MUST offer an equivalent flat list view of the same
  ancestor set, with generation number shown per person.
- **FR-004**: System MUST allow filtering the list view by generation.
- **FR-005**: System MUST allow the visitor to bound the traversal to a
  maximum number of generations.
- **FR-006**: System MUST show an explicit "unknown" slot for a missing
  parent rather than omitting it silently.
- **FR-007**: System MUST apply the same privacy rule to ancestor nodes as to
  standalone profiles.
- **FR-008**: System MUST traverse ancestors across lineage/team boundaries
  wherever a recorded relationship connects them, per spec 002.
- **FR-009**: System MUST link every node in the tree or list to that
  person's own profile (spec 003).

### Key Entities

- **Person**: unchanged; read/traversal feature only.
- **Ancestor relationship**: derived via `father_id`/`mother_id` links and
  the existing recursive query engine (`app/Queries/*AncestorsQuery.php`).

### Team/Lineage scope interaction *(Constitution Principle VI & VII)*

This spec reuses `app/Queries/{MySql,PgSql,SQLite}AncestorsQuery.php`
unchanged in their core recursive logic. As with spec 004, the existing
team-scoping parameters MUST be adapted so traversal crosses team boundaries
wherever a genealogical relationship does, consistent with spec 002.

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- `GET /p/{person}/ancestors` — new **public** route, under the same `/p/`
  prefix as specs 003/004, mirroring spec 004's URI-collision fix: it MUST
  NOT reuse the literal `people/{person}/ancestors` path, which is already
  registered (authenticated, `people.ancestors`,
  `Back\PeopleController@ancestors` per `routes/web.php`) — Laravel would
  only serve whichever route registers first for a duplicate URI+method
  pair, regardless of route name.

### Livewire Components

- `AncestorExplorer` — top-level component, same Tree/List tab shell as
  `DescendantExplorer` (spec 004), generation-limit control, shared root
  header.
- `AncestorTree` — mirrors `DescendantTree` but traverses upward; an
  "unknown parent" slot (User Story 3) renders as a dashed-border placeholder
  card labeled "Parent inconnu" instead of being omitted, keeping the tree's
  two-parents-per-generation layout intact.
- `AncestorList` — TallStackUI table: Generation / Name / Lineage / Birth
  year, generation filter, mirrors `DescendantList` (spec 004).

### Key Screen States

- **Root only, no ancestors**: root card alone plus "Aucun ancêtre
  enregistré."
- **Partial generation** (one parent known, one unknown): both slots render
  side by side — known parent as a normal linkable card, unknown parent as
  the dashed placeholder (User Story 3) — never a collapsed/missing slot.
- **Branch loading / generation limit reached / living ancestor node /
  cross-lineage node**: identical behavior and visual treatment to
  `DescendantTree` (spec 004), for interface consistency between the two
  explorers.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A visitor can open an ancestor tree and expand three levels of
  branches without a full page reload at any step.
- **SC-002**: Ancestor trees crossing two or more lineages display as one
  connected tree, with zero manual "merge" steps.
- **SC-003**: A person with only partial ancestor data (missing parent) still
  renders a complete, non-broken tree with explicit unknown slots.

## Assumptions

- Default initial expansion depth mirrors the descendant explorer's default
  (spec 004 Assumptions) for interface consistency.
- Charting/rendering technology is intentionally unspecified here, same
  rationale as spec 004.
- This spec and spec 004 are expected to share the bulk of their
  implementation (same tree/list UI shell, opposite traversal direction);
  they are kept as separate specs because each is independently testable and
  independently valuable, per Spec Kit's story-independence guidance.
