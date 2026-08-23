# Feature Specification: Lineages

**Feature Branch**: `001-lineages`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Create a Lineage entity representing a family line (e.g. RAKOTO, RABE), and let a person belong to more than one lineage at once, so that connected families are represented as one graph instead of separate isolated trees."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create and describe a lineage (Priority: P1)

A contributor creates a new lineage ("RAKOTO") with a name, description, and
optional origin/region, so that people can start being attached to it.

**Why this priority**: Without a lineage to attach people to, nothing else in
the MVP (person profile lineage tags, descendant/ancestor exploration by
lineage, search by lineage) can be demonstrated.

**Independent Test**: Create a lineage through the UI, verify it appears in
the lineage list with the entered name/description, with zero people attached.

**Acceptance Scenarios**:

1. **Given** a logged-in contributor, **When** they create a lineage named
   "RAKOTO" with a description, **Then** the lineage is saved and appears in
   the lineage directory with a unique, human-readable URL slug.
2. **Given** an existing lineage named "RAKOTO", **When** a contributor tries
   to create another lineage also named "RAKOTO", **Then** the system either
   rejects the duplicate name or clearly warns that a lineage with this name
   already exists before allowing the save (see Edge Cases).

---

### User Story 2 - Attach a person to one or more lineages (Priority: P1)

A contributor attaches an existing person to a lineage, and separately
attaches the same person to a second lineage (e.g. because they married into
another family), without creating a second copy of that person.

**Why this priority**: This is the exact capability the current data model
cannot express (see `specs/000-project-foundation/spec.md` §1.2) and is the
reason this spec exists — it's the MVP's core structural unlock.

**Independent Test**: Attach person "Jean RAKOTO" to lineage "RAKOTO", then
also attach him to lineage "RABE" (his wife's lineage). Verify his profile
lists both lineages and his `Person` row is unchanged/unique.

**Acceptance Scenarios**:

1. **Given** a person not yet attached to any lineage, **When** a contributor
   attaches them to lineage "RAKOTO", **Then** the person's profile shows
   "RAKOTO" as one of their lineages.
2. **Given** a person already attached to lineage "RAKOTO", **When** a
   contributor attaches the same person to lineage "RABE", **Then** the
   person's profile shows both lineages and no duplicate `Person` record is
   created anywhere in the system.
3. **Given** a person attached to two lineages, **When** a contributor detaches
   them from one lineage, **Then** the person remains attached to the other
   lineage and their core biographical data is untouched.

---

### User Story 3 - Browse a lineage's members (Priority: P2)

A contributor or visitor opens a lineage's page and sees the list of people
currently attached to it.

**Why this priority**: Makes the lineage concept visible and useful once
people are attached to it; depends on User Story 1 and 2 being done first.

**Independent Test**: Open the "RAKOTO" lineage page and verify every person
attached to it (per User Story 2) appears in the member list.

**Acceptance Scenarios**:

1. **Given** a lineage with 5 attached people, **When** a contributor opens
   the lineage page, **Then** all 5 people are listed with enough information
   (name, birth/death years) to identify them.
2. **Given** a lineage with zero attached people, **When** anyone opens the
   lineage page, **Then** the page shows an empty state rather than an error.

---

### Edge Cases

- What happens when a lineage name is a near-duplicate of an existing one
  (e.g. "Rakoto" vs "RAKOTO")? → Name comparison for the duplicate-name warning
  in User Story 1 MUST be case-insensitive and trim whitespace.
- What happens when a contributor tries to detach the last remaining lineage
  from a person? → Allowed; a person MAY exist with zero lineages (e.g. newly
  created, not yet classified).
- What happens when a lineage is deleted while people are still attached to
  it? → The lineage MUST NOT be deletable while it has one or more people
  attached (mirrors the existing `Team::isDeletable()` / `Person::isDeletable()`
  integrity pattern already in the codebase).
- What happens when the same person is attached to the same lineage twice? →
  The attach action MUST be idempotent; attaching an already-attached person
  again produces no duplicate membership row and no error.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow a contributor to create a lineage with a
  required name and optional description, origin/region, and cover image.
- **FR-002**: System MUST generate a unique, URL-safe slug for each lineage
  derived from its name.
- **FR-003**: System MUST warn a contributor when creating a lineage whose
  name matches (case-insensitively) an existing lineage's name.
- **FR-004**: System MUST allow a contributor to attach any existing person to
  any existing lineage.
- **FR-005**: System MUST allow a person to be attached to any number of
  lineages simultaneously (zero, one, or many) without duplicating the
  person's record.
- **FR-006**: System MUST allow a contributor to detach a person from a
  lineage without affecting the person's other lineage memberships or
  biographical data.
- **FR-007**: System MUST prevent deletion of a lineage that still has one or
  more people attached to it.
- **FR-008**: System MUST display, on a person's profile, the full list of
  lineages that person is attached to.
- **FR-009**: System MUST display, on a lineage's page, the full list of
  people attached to that lineage.
- **FR-010**: Attaching an already-attached person to the same lineage MUST be
  a no-op (idempotent), not an error and not a duplicate row.

### Key Entities

- **Lineage**: A named family line (e.g. "RAKOTO"). Attributes: name, slug,
  description, origin/region, cover image, status. Independent of any single
  person or team — multiple people can belong to it, and it survives even if
  its "founder" person record changes.
- **Lineage Membership**: The connection between one `Person` and one
  `Lineage`. A person can have zero, one, or many memberships. Carries no
  meaning beyond "this person is considered part of this lineage" in this
  spec (relationship *type*, e.g. "by birth" vs "by marriage", is explicitly
  out of scope here — see Assumptions).

### Team/Lineage scope interaction *(Constitution Principle VII)*

Lineage membership is independent of `team_id`/the existing `team` global
scope on `Person`/`Couple`. This spec does NOT remove or bypass that scope —
a contributor still only *sees and edits* people within their current team's
existing permission boundary. What changes is that a person's identity is no
longer tied 1:1 to a single tree/family concept: `Lineage` is the new concept
for "which family line(s) does this person belong to," while `team_id`
continues, for now, to answer "who is allowed to edit this person." Spec
002 addresses whether/how these two concerns are further decoupled.

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- `GET /lineages` — public lineage directory (list of all lineages, searchable).
- `GET /lineages/{lineage:slug}` — public lineage page (User Story 3).
- `GET /back/lineages` — contributor-facing management list (create/edit/delete).
- Person edit screen (`people/{person}/edit-profile` or a new dedicated
  panel) gains a "Lineages" section for attach/detach (User Story 2).

### Livewire Components

- `LineageForm` — create/edit form (name, description, origin/region, cover
  image). Debounced `wire:model.live` on the name field triggers an inline
  duplicate-name check (FR-003) rendering a dismissible warning banner above
  the field without blocking submission.
- `LineageDirectory` — public list, TallStackUI table/grid, paginated, with a
  name filter input.
- `LineageShow` — public lineage page: header (name, description, cover),
  member list (name, lifespan, link to profile), empty state when zero
  members ("Aucune personne rattachée pour l'instant").
- `PersonLineageManager` — embedded widget on the person edit screen: an
  autocomplete/multi-select to attach existing lineages, and a chip/tag list
  of currently attached lineages each with a "detach" (×) action that opens a
  confirm step before removing.

### Key Screen States

- **Loading**: skeleton rows in `LineageDirectory`/`LineageShow` member list
  while paginated results fetch.
- **Empty**: lineage with zero members (User Story 3, Acceptance Scenario 2)
  and lineage directory with zero lineages both render explicit empty states,
  not blank space.
- **Blocked action**: attempting to delete a lineage with members (FR-007)
  disables the delete action with a tooltip/inline message explaining why,
  rather than allowing the click and failing server-side only.
- **Idempotent attach**: re-attaching an already-attached person shows the
  existing chip already present — no duplicate chip, no error toast.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A contributor can create a lineage and attach an existing person
  to it in under 1 minute.
- **SC-002**: 100% of people attached to two or more lineages remain
  represented by exactly one person record (zero duplication, verifiable by a
  uniqueness check on person identity across all lineage memberships).
- **SC-003**: A visitor opening any lineage page with up to 500 members sees
  the member list load without a perceptible delay (page feels instant).

## Assumptions

- Lineage membership in this spec is a simple, untyped attachment ("this
  person belongs to this lineage"). Distinguishing membership *by birth* vs
  *by marriage* vs *by adoption* is a reasonable follow-up refinement but is
  NOT required for the MVP; it is not blocked by this spec's data shape.
- A lineage's "founder" is descriptive metadata only in this spec, not a
  structural requirement — a lineage can exist and have members without a
  designated founder.
- Only contributors and above (per existing role system) can create lineages
  or attach/detach people; visitors can only browse (User Story 3).
