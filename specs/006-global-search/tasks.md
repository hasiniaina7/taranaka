---

description: "Task list for 006-global-search"
---

# Tasks: Global Search

**Input**: Design documents from `/specs/006-global-search/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: Included — Constitution Principle V (Test-First, NON-NEGOTIABLE) requires a Pest test before every new/changed unit of business logic; the feature spec's Edge Cases and User Story 3 also explicitly require privacy and cross-team assertions.

**Organization**: Tasks are grouped by user story (US1, US2, US3) so each can be implemented and tested independently. US1 and US2 are both P1; US3 is P2.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Maps the task to US1/US2/US3
- File paths are exact, taken from plan.md's Project Structure section

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm the feature's file scaffolding exists before any story work begins.

- [ ] T001 Verify `app/Http/Controllers/Front/` and `app/Livewire/` directories exist and confirm no existing `SearchController`, `SearchBar`, or `SearchResults` classes conflict with this feature (repository root)
- [ ] T002 [P] Create `tests/Feature/Search/` directory for the new Pest feature tests

**Checkpoint**: Scaffolding confirmed — foundational work can begin.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared building blocks every user story depends on — the `Lineage` search scope, the public route, and the `SearchController` skeleton that both Livewire components call into.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [ ] T003 [P] Add `Lineage::scopeSearch(Builder $query, string $searchString): void` in `app/Models/Lineage.php`, mirroring `Person::scopeSearch()`'s guard (empty/`%`-only input) and `$escapeLike` pattern, matching against `name` with a single LIKE clause
- [ ] T004 [P] Add `Route::get('search', [SearchController::class, 'show'])->name('public.search')` in `routes/web.php`, outside the authenticated group and distinct from the existing `people.search` route
- [ ] T005 Create `app/Http/Controllers/Front/SearchController.php` with a `show(Request $request): View` action that reads the `q` query parameter and renders the public search page hosting the `SearchResults` Livewire component
- [ ] T006 Mount the `SearchBar` Livewire component in the public layout's header/nav (the shared layout Blade file used by home, profile, lineage, and explorer pages) so search is present on every public page

**Checkpoint**: Foundation ready — US1, US2, US3 implementation can now begin.

---

## Phase 3: User Story 1 - Search for a person by name (Priority: P1) 🎯 MVP

**Goal**: A visitor searches a name and sees all matching, publicly visible people across every team/lineage, each disambiguated by lifespan and lineage.

**Independent Test**: Seed several "Jean Rakoto" people with different birth years across teams; search "Jean Rakoto" as a signed-out visitor and verify all public matches appear, each showing distinguishing lifespan/lineage detail.

### Tests for User Story 1 ⚠️

> Write these tests FIRST, ensure they FAIL before implementation.

- [ ] T007 [P] [US1] Feature test in `tests/Feature/Search/SearchAcrossTeamsTest.php`: search from a guest and from an authenticated user of a different team both return people belonging to a team the searcher doesn't belong to, proving `withoutGlobalScope('team')` is applied explicitly (not by accident of guest state)
- [ ] T008 [P] [US1] Feature test in `tests/Feature/Search/SearchDisambiguatesSameNameResultsTest.php`: several same-name people with different birth years/lineages all appear in `/search?q=...` results, each row exposing lifespan and lineage
- [ ] T009 [P] [US1] Feature test in `tests/Feature/Search/SearchEscapesSpecialCharactersTest.php`: a search term containing `%` and `_` is treated literally (no over-broad match), reusing the `Person::scopeSearch()` escaping behavior
- [ ] T010 [P] [US1] Feature test in `tests/Feature/Search/SearchCapsResultVolumeTest.php`: seeding more people than the page cap returns a truncated, paginated result set with the "Affiner votre recherche" indicator surfaced

### Implementation for User Story 1

- [ ] T011 [US1] Implement person search query in `app/Http/Controllers/Front/SearchController.php`: `Person::withoutGlobalScope('team')->search($q)->paginate(...)`, documented inline as an explicit, intentional scope bypass per spec.md's Team/Lineage scope interaction section
- [ ] T012 [US1] Create `app/Livewire/SearchBar.php` + Blade view: text input bound with `wire:model.live.debounce.300ms`, inline autocomplete dropdown of top few person matches, "no results yet — keep typing" state below the minimum term length
- [ ] T013 [US1] Create `app/Livewire/SearchResults.php` + Blade view: "Personnes" TallStackUI list section, paginated independently, each row showing name, lifespan, lineage tags; empty-prompt state when no query, "Aucun résultat pour « … »" state when zero matches, and the "Affiner votre recherche" hint when the result cap is hit
- [ ] T014 [US1] Link each person result row to its profile route (spec 003) from both `SearchBar`'s dropdown and `SearchResults`

**Checkpoint**: User Story 1 is fully functional and independently testable — a visitor can search and find people across teams.

---

## Phase 4: User Story 2 - Search for a lineage by name (Priority: P1)

**Goal**: A visitor searching a family name sees lineage results in a section clearly distinct from person results.

**Independent Test**: With a "Rakoto" lineage and several Rakoto-surnamed people seeded, search "Rakoto" and verify the lineage result appears in a separately labeled section from the person results.

### Tests for User Story 2 ⚠️

> Write these tests FIRST, ensure they FAIL before implementation.

- [ ] T015 [P] [US2] Feature test in `tests/Feature/Search/SearchLineageResultsTest.php`: searching a name matching both a `Lineage` and several `Person` records returns both, each labeled/grouped under its own section (e.g. "Personnes" vs. "Lignées")

### Implementation for User Story 2

- [ ] T016 [US2] Add lineage search query in `app/Http/Controllers/Front/SearchController.php`: `Lineage::search($q)->paginate(...)` using the new `Lineage::scopeSearch()` from T003
- [ ] T017 [US2] Extend `app/Livewire/SearchResults.php` + Blade view with a "Lignées" TallStackUI list section, paginated independently of "Personnes" (FR-007), each row showing name and linking to the lineage page (spec 001)
- [ ] T018 [US2] Extend `app/Livewire/SearchBar.php`'s autocomplete dropdown to include top lineage matches alongside person matches, visually distinguished

**Checkpoint**: User Stories 1 AND 2 both work independently — search returns and distinguishes person and lineage results.

---

## Phase 5: User Story 3 - Living people appear in search without exposing private data (Priority: P2)

**Goal**: A search match on a living, non-opted-in person shows only name and a private indicator, with no sensitive fields, in both the autocomplete dropdown and the full results page.

**Independent Test**: Search a name matching a known living, non-opted-in person and verify the result shows name + private indicator only; the raw response contains no address/phone/exact birth date.

### Tests for User Story 3 ⚠️

> Write these tests FIRST, ensure they FAIL before implementation.

- [ ] T019 [P] [US3] Feature test in `tests/Feature/Search/SearchWithholdsLivingPersonSensitiveFieldsTest.php`: a living, non-opted-in person matching the search term appears with name + private indicator only — asserting the raw HTTP response does NOT contain address/phone/exact birth date, and that the projection used is `PersonPrivacy::publicFields()` (spec 003) rather than a parallel implementation
- [ ] T020 [P] [US3] Feature test in `tests/Feature/Search/SearchAcrossTeamsTest.php` (extend from T007): a guest and an authenticated user belonging to a different team both see the identical privacy-filtered result for the same living person, proving the privacy filter — not team membership or auth state — governs visibility (spec Edge Cases)

### Implementation for User Story 3

- [ ] T021 [US3] Apply `PersonPrivacy::publicFields()` (`app/Support/PersonPrivacy.php`, spec 003) to every person row in `app/Http/Controllers/Front/SearchController.php`'s person query results before passing them to `SearchResults`, so no controller/view path can bypass the projection
- [ ] T022 [US3] Update `resources/views/livewire/search-results.blade.php` to render the shared privacy badge/indicator (spec 003/007 component) in place of lifespan/lineage detail for a living, non-opted-in match
- [ ] T023 [US3] Update `resources/views/livewire/search-bar.blade.php`'s autocomplete dropdown rows to apply the same privacy projection/badge as the full results page

**Checkpoint**: All user stories are independently functional — search works across teams, distinguishes lineage from person results, and never leaks private fields for living people.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across all stories.

- [ ] T024 [P] Run `vendor/bin/sail bin pint --dirty --format agent` to fix formatting on all files touched in this feature
- [ ] T025 Run `vendor/bin/sail artisan test --compact --filter=Search` and confirm all tests in `tests/Feature/Search/` pass
- [ ] T026 Walk through quickstart.md sections 1-4 manually against a seeded dataset (cross-team person search, lineage search, living-person privacy, escaping/result cap) and confirm each "Expected" outcome holds

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately.
- **Foundational (Phase 2)**: Depends on Setup — BLOCKS all user stories.
- **User Story 1 (Phase 3)**: Depends on Foundational only.
- **User Story 2 (Phase 4)**: Depends on Foundational; extends `SearchController`/`SearchResults`/`SearchBar` from US1 but is independently testable (the "Lignées" section exists even if no US1 changes are further modified).
- **User Story 3 (Phase 5)**: Depends on Foundational and on the person query path established in US1 (T011) since it wraps that query's output; independently testable via its own dedicated test.
- **Polish (Phase 6)**: Depends on all desired user stories being complete.

### Within Each User Story

- Tests are written first and MUST fail before implementation begins.
- `SearchController` query logic before Livewire component wiring.
- `SearchBar`/`SearchResults` component logic before Blade view polish.

### Parallel Opportunities

- T003 and T004 (Foundational) touch different files and can run in parallel.
- All US1 tests (T007-T010) can run in parallel — different files.
- The US3 test (T019) and the US1/US3 combined test extension (T020) can be drafted in parallel with US2's test (T015) since they touch different files.
- T024 (Pint) can run in parallel with T026 (manual quickstart walkthrough) since they don't share files.

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Feature test in tests/Feature/Search/SearchAcrossTeamsTest.php"
Task: "Feature test in tests/Feature/Search/SearchDisambiguatesSameNameResultsTest.php"
Task: "Feature test in tests/Feature/Search/SearchEscapesSpecialCharactersTest.php"
Task: "Feature test in tests/Feature/Search/SearchCapsResultVolumeTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup.
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories).
3. Complete Phase 3: User Story 1.
4. **STOP and VALIDATE**: run `SearchAcrossTeamsTest`, `SearchDisambiguatesSameNameResultsTest`, `SearchEscapesSpecialCharactersTest`, `SearchCapsResultVolumeTest`; confirm US1's Independent Test passes manually.
5. Demo: a visitor can search a person name and get disambiguated, cross-team results — this alone satisfies the primary entry-point goal (FR-001, FR-002, FR-004, FR-006, FR-007).

### Incremental Delivery

1. Setup + Foundational → foundation ready (route, `SearchController` skeleton, `Lineage::scopeSearch()`, `SearchBar` mounted globally).
2. Add User Story 1 → validate independently → MVP demoable.
3. Add User Story 2 → validate independently → lineage results now distinguishable (FR-003).
4. Add User Story 3 → validate independently → privacy guarantee closed (FR-005, SC-002) — ship only after this, since Principle II/VIII make it a release blocker, not an optional enhancement.
5. Polish → pint, full `--filter=Search` suite, quickstart.md walkthrough.

### Parallel Team Strategy

1. Team completes Setup + Foundational together (T001-T006).
2. Once Foundational is done: Developer A takes US1 (Phase 3), Developer B takes US2 (Phase 4) once US1's `SearchController`/`SearchResults` skeleton lands, Developer C takes US3 (Phase 5) once US1's person query (T011) exists.
3. Stories complete and integrate through the shared `SearchController`/`SearchResults`/`SearchBar` files — coordinate on those files to avoid conflicting edits.

---

## Notes

- [P] tasks touch different files and have no ordering dependency on each other.
- [Story] labels (US1/US2/US3) map every story-phase task to its user story for traceability.
- Tests are written first per Constitution Principle V and MUST fail before their corresponding implementation task is started.
- No parallel search engine, fuzzy matching, or place/date search is in scope (spec.md Assumptions) — do not add tasks for these.
- `PersonPrivacy::publicFields()` (spec 003, `app/Support/PersonPrivacy.php`) is reused as-is; no new privacy logic is created for search (data-model.md).
- Commit after each task or logical group; stop at each phase checkpoint to validate that story independently before continuing.
