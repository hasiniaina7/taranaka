# Feature Specification: Duplicate Detection

**Feature Branch**: `009-duplicate-detection`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Before a new person is created, warn the contributor if a similar person might already exist, with a similarity score, so the same real individual doesn't end up duplicated across the graph — building on the existing basic name-matching already in the codebase."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Warn before creating a likely-duplicate person (Priority: P1)

A contributor starts creating a new person named "Jean Rakoto, born 1954".
Before saving, the system shows existing people that closely match this name
and birth information, with a similarity indicator.

**Why this priority**: This directly protects Constitution Principle I (one
person, one entity) at the exact moment duplication risk is highest — during
manual creation. Without this, spec 001/002's entire premise (a connected,
non-duplicated graph) erodes one bad data-entry session at a time.

**Independent Test**: With an existing person "Jean Rakoto, born 1954" in the
database, start creating a new person with the same name and birth year, and
verify a duplicate warning appears before save, listing the existing match.

**Acceptance Scenarios**:

1. **Given** an existing person matching the name and approximate birth year
   being entered, **When** a contributor is about to save a new person,
   **Then** the system shows the existing match(es) with a similarity
   indicator before the save completes.
2. **Given** no existing person resembles the one being entered, **When** a
   contributor saves, **Then** no warning is shown and creation proceeds
   normally.

---

### User Story 2 - Contributor resolves a duplicate warning (Priority: P1)

Faced with a duplicate warning, a contributor either confirms the existing
person is the same individual (and is guided to attach/link rather than
create), or confirms it's genuinely a different person and proceeds to
create.

**Why this priority**: A warning with no resolution path is just friction —
this is what makes the detection actionable rather than merely informative.

**Independent Test**: Trigger a duplicate warning, choose "this is the same
person," and verify no new person record is created; separately, trigger a
warning, choose "this is a different person," and verify creation proceeds.

**Acceptance Scenarios**:

1. **Given** a duplicate warning listing an existing match, **When** the
   contributor selects that existing person as the same individual, **Then**
   no new person record is created and the contributor is directed to use
   the existing person instead (e.g. to attach them to a lineage or
   relationship).
2. **Given** the same warning, **When** the contributor explicitly confirms
   this is a different person, **Then** the new person is created normally
   and the decision is recorded for traceability.

---

### User Story 3 - Similarity score reflects multiple signals, not name alone (Priority: P2)

The similarity indicator accounts for name closeness AND birth year closeness
(and, where available, parent/lineage overlap), not just an exact or
substring name match.

**Why this priority**: A name-only match (the current `scopeSimilarTo`
baseline per the 000 audit) produces too many false positives/negatives to be
trustworthy at scale; combining signals is what makes the warning worth
paying attention to, but it's a refinement on top of User Story 1/2 working
at all.

**Independent Test**: Compare two same-name people with very different birth
years and verify the similarity score is meaningfully lower than for two
same-name people with matching birth years.

**Acceptance Scenarios**:

1. **Given** two people with the same name but birth years 40 years apart,
   **When** duplicate detection runs, **Then** the resulting similarity score
   is low enough that no warning is shown (or it's clearly marked low
   confidence).
2. **Given** two people with the same name and matching (or both unknown)
   birth years, **When** duplicate detection runs, **Then** the similarity
   score is high enough to trigger a warning.

---

### Edge Cases

- What happens when neither person being compared has a recorded birth year?
  → Name similarity alone drives the score in that case; the score MUST be
  clearly presented as lower-confidence than a match corroborated by birth
  year.
- What happens when a contributor repeatedly dismisses warnings for
  genuinely distinct people who happen to share a common name? → Each
  decision (User Story 2) MUST be recorded but MUST NOT suppress future
  warnings for other, different comparisons — no global "stop warning me"
  toggle that would blind the contributor to real duplicates.
- What happens when duplicate detection is run against people in a different
  lineage/team than the one being created in? → It MUST still check across
  all teams/lineages (mirrors spec 006 Global Search's cross-team scope),
  since Principle I applies platform-wide, not per-team.
- What happens during a bulk import (e.g. future GEDCOM import)? → Explicitly
  out of scope for this spec; bulk-import duplicate handling is deferred to
  the GEDCOM spec (hors MVP).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST check for potentially matching existing people
  before a new person record is committed, using name and, when available,
  birth year.
- **FR-002**: System MUST present a similarity score or clear confidence
  level (not just a binary match/no-match) for each candidate match shown.
- **FR-003**: System MUST search for candidate matches across all
  teams/lineages, not scoped to the creating contributor's current team.
- **FR-004**: System MUST allow the contributor to resolve a warning either
  by linking to/reusing the existing person or by explicitly confirming a
  new, distinct person.
- **FR-005**: System MUST record the contributor's resolution decision
  (linked-as-same vs. confirmed-distinct) for traceability.
- **FR-006**: System MUST NOT block creation outright — the contributor
  retains the final decision; the system's role is to warn, not to enforce.
- **FR-007**: System MUST extend the existing `Person::scopeSimilarTo` name
  matching with birth-year proximity as an additional signal (Constitution
  Principle VI encourages reuse over rewrite of existing query logic where
  applicable).

### Key Entities

- **Person**: unchanged; this spec adds a pre-save comparison, not new
  stored fields on Person itself.
- **Duplicate Resolution Decision**: records which candidate(s) were shown,
  and whether the contributor linked to an existing person or confirmed a
  new one — feeds traceability, not a new user-facing entity.

### Team/Lineage scope interaction *(Constitution Principle VII)*

Duplicate candidate search MUST bypass the `team` global scope on `Person`,
the same deliberate exception already established for spec 006 (Global
Search) — for the same reason: Constitution Principle I ("one person, one
entity") is a platform-wide invariant, not a per-team one.

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- No new route — this spec augments the existing `people/add` creation flow
  (`PersonForm`, per the 000 audit).

### Livewire Components

- `PersonForm` — gains a `wire:model.live.debounce.500ms` check on
  name+birth-year fields that queries candidate matches as the contributor
  types, before any save attempt (FR-001).
- `DuplicateWarningPanel` — inline panel (not a blocking modal, per FR-006)
  appearing below the name/birth fields once candidates are found: each
  candidate shown as a compact card (name, lifespan, lineage, similarity
  score as a percentage/bar, per FR-002) with two actions per candidate:
  "C'est la même personne → lier" and, once any candidate is dismissed as
  distinct, an overall "Ce sont des personnes différentes → continuer"
  confirmation control that must be explicitly clicked when a
  high-similarity match exists, before the main Save button is enabled
  (soft-gate, not a hard block — save remains possible after this explicit
  acknowledgment).

### Key Screen States

- **No candidates found**: `DuplicateWarningPanel` stays hidden entirely; the
  form behaves exactly as today.
- **Low-confidence candidate(s)** (e.g. name-only match, no birth year on
  either side): panel shows candidates marked "Correspondance faible" and
  does NOT require explicit acknowledgment before saving.
- **High-confidence candidate(s)**: panel is prominent (warning color), and
  the Save button requires the acknowledgment control described above before
  becoming active.
- **Linked instead of created**: choosing "lier" redirects the contributor to
  the existing person's edit/attach flow (e.g. into spec 001's
  `PersonLineageManager`) instead of completing a new-person save.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: When creating a person that closely matches (same name, same or
  close birth year) an existing record, a warning is shown before save in at
  least 95% of such cases in test data.
- **SC-002**: When creating a person with a common name but a clearly
  different birth year (20+ years apart) from any existing match, no warning
  is shown (false-positive rate kept low enough not to train contributors to
  ignore warnings).
- **SC-003**: 100% of duplicate-warning resolutions (link vs. confirm-new)
  are recorded and later reviewable.

## Assumptions

- The similarity scoring approach for MVP is a straightforward weighted
  combination of name closeness and birth-year proximity; advanced
  probabilistic/ML matching is explicitly out of scope, consistent with the
  product cadrage document's "hors périmètre initial" (no AI reconstruction).
- Detection runs synchronously at person-creation time in the UI; a
  background/batch duplicate scan across the entire existing dataset (to
  catch duplicates already present before this spec shipped) is a reasonable
  follow-up but not required for MVP.
