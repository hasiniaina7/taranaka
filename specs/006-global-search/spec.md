# Feature Specification: Global Search

**Feature Branch**: `006-global-search`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "A single search box lets anyone find a person or a lineage by name, across the whole database, respecting privacy for living people, reusing the existing person search logic."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Search for a person by name (Priority: P1)

A visitor types a name (e.g. "Jean Rakoto") into a search box and sees
matching people, distinguishable by lifespan and lineage when multiple people
share a name.

**Why this priority**: Search is the primary entry point into the graph for
anyone who doesn't already have a direct link to a profile — without it,
specs 003/004/005 are only reachable by accident.

**Independent Test**: Search "Rakoto" against a dataset containing several
people with that surname and verify all matching, publicly visible people
appear, each disambiguated by lifespan/lineage.

**Acceptance Scenarios**:

1. **Given** several people named "Jean Rakoto" with different birth years,
   **When** a visitor searches "Jean Rakoto", **Then** all matching public
   results appear, each showing enough distinguishing detail (birth/death
   year, lineage) to tell them apart.
2. **Given** a search term matching zero people, **When** a visitor searches,
   **Then** a clear "no results" state is shown, not an error or a blank
   page.

---

### User Story 2 - Search for a lineage by name (Priority: P1)

A visitor types a family name (e.g. "Rakoto") and can choose to see lineage
results (spec 001) alongside or instead of person results.

**Why this priority**: The product's vision (F07) explicitly separates
"search a person" from "search a family" — both are core discovery paths for
the visitor persona.

**Independent Test**: Search "Rakoto" and verify the "Rakoto" lineage (if it
exists) appears as a distinct result type from individual people named
Rakoto.

**Acceptance Scenarios**:

1. **Given** a lineage named "Rakoto" and several people surnamed "Rakoto",
   **When** a visitor searches "Rakoto", **Then** results are grouped or
   labeled so the lineage result and the person results are clearly
   distinguishable.

---

### User Story 3 - Living people appear in search without exposing private data (Priority: P2)

A visitor searches for a name that matches a living person.

**Why this priority**: Search must not become a loophole around the privacy
rule enforced on profiles (spec 003/007) — search results are often the
first thing rendered, so this must be verified explicitly.

**Independent Test**: Search for a name matching a known living person and
verify the result shows only name and a "living/private" indicator, no
address/phone/exact birth date.

**Acceptance Scenarios**:

1. **Given** a living person matching the search term, **When** a visitor
   searches, **Then** the result shows their name and a private indicator,
   without private fields, and links to their privacy-limited profile (spec
   003 User Story 2).

---

### Edge Cases

- What happens with a very short or overly broad search term (e.g. a single
  letter)? → System MAY require a minimum term length before returning
  results, to avoid overwhelming/expensive broad scans (mirrors the existing
  `scopeSearch` guard against empty/`%`-only input).
- What happens when search results would exceed a reasonable page size? →
  Results MUST be paginated or capped with a "refine your search" indicator
  rather than returning unbounded results.
- What happens when a search term contains special characters used in SQL
  LIKE patterns (`%`, `_`)? → These MUST be escaped, consistent with the
  existing `Person::scopeSearch()` escaping behavior.
- What happens when the same search is run by a signed-out visitor vs. an
  authenticated contributor? → The privacy filter (User Story 3) applies
  identically regardless of authentication state — being logged in to some
  team does not itself grant visibility into a living person owned by
  another team; only explicit edit rights on that person do.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST provide a single search entry point that accepts a
  free-text name query.
- **FR-002**: System MUST return matching people across all teams/lineages,
  not scoped to the searching user's current team.
- **FR-003**: System MUST return matching lineages, labeled distinctly from
  person results.
- **FR-004**: System MUST disambiguate same-name person results using
  lifespan and lineage information.
- **FR-005**: System MUST apply the same privacy rule as spec 003/007 to
  living people appearing in results: name and private indicator only, no
  sensitive fields.
- **FR-006**: System MUST escape search-term characters that have special
  meaning in the underlying pattern-matching query.
- **FR-007**: System MUST cap or paginate result volume rather than returning
  unbounded matches.
- **FR-008**: System MUST link each person result to their profile (spec
  003) and each lineage result to its lineage page (spec 001).

### Key Entities

- **Person** / **Lineage**: existing entities, read-only for this spec.

### Team/Lineage scope interaction *(Constitution Principle VII)*

Search MUST NOT apply the existing `team` global scope on `Person`/`Couple` —
that scope is a permission/visibility rule for team members, not a public
discovery boundary. Search results are governed solely by the privacy rule
(spec 007 / Constitution Principle VIII), not by team membership. This is a
deliberate, explicit exception to the default scope and MUST be implemented
as such (not by accident of running as a guest, per spec 002 Edge Cases).

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- `GET /search?q=...` — public search results page.
- `SearchBar` is mounted in the public layout's header/nav, present on every
  public page (home, profile, lineage, explorers) so search is always one
  action away, not only reachable from a dedicated page.

### Livewire Components

- `SearchBar` — text input with `wire:model.live.debounce.300ms`, shows an
  inline autocomplete dropdown (top few matches) before the user even
  submits, and a "no results yet — keep typing" state below the minimum term
  length (Edge Cases).
- `SearchResults` — full results page reached on submit/enter: two labeled
  sections ("Personnes" / "Lignées", User Story 2) rendered as separate
  TallStackUI lists, each paginated independently (FR-007). Each person
  result row shows name, lifespan, lineage tags, and — for a living,
  non-opted-in match — the shared privacy badge (spec 003/007) in place of
  any distinguishing detail beyond name.

### Key Screen States

- **No query yet**: `SearchBar` shows a placeholder prompt, `SearchResults`
  page (if reached directly) shows an empty prompt state, not "no results."
- **No matches**: explicit "Aucun résultat pour « … »" message, distinct
  from the no-query state.
- **Ambiguous same-name matches**: results list disambiguating detail
  (lifespan/lineage) is always visible inline per row — never requires a
  hover or click to reveal (User Story 1, FR-004).
- **Result cap reached**: a "Affiner votre recherche" hint shown when the
  capped result count is hit (FR-007), so the visitor knows results were
  truncated rather than assuming completeness.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A visitor can find a specific person by full name in a single
  search, with results distinguishing same-name matches, in under 1 second
  for a database of up to 50,000 people.
- **SC-002**: 0% of living persons' sensitive fields appear in any search
  result.
- **SC-003**: A search for an existing lineage name surfaces that lineage
  result distinctly from person results in the same query.

## Assumptions

- Full-text/relevance ranking sophistication (e.g. fuzzy matching, phonetic
  search) is out of scope for MVP; reuses the existing multi-word LIKE-based
  matching in `Person::scopeSearch()` extended to run without team scoping.
- A dedicated search engine (Meilisearch or similar) is explicitly deferred
  per the product cadrage document (§3.8) — this spec targets MySQL-based
  search only, consistent with Constitution Principle IV.
- Search by place, date range, or event type (mentioned as a possible future
  extension in the product vision) is out of scope for this spec.
