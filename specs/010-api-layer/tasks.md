---

description: "Task list for Read-Only API Layer (010-api-layer)"
---

# Tasks: Read-Only API Layer

**Input**: Design documents from `/specs/010-api-layer/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/routes.md, quickstart.md

**Tests**: MANDATORY per Constitution Principle V and this feature's core deliverable (research.md: parity tests are the PRIMARY test strategy). Every endpoint task pairs with a parity test written and failing FIRST, asserting the API JSON response equals a transform of the exact same data the corresponding web page/controller renders — never an independently re-derived expectation.

**Organization**: Tasks are grouped by user story (US1/US2/US3) per spec.md priorities, each independently testable and shippable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Maps task to US1, US2, or US3
- Exact file paths are taken from plan.md's Project Structure and contracts/routes.md's endpoint table.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Provision the `v1` route group and namespace this feature adds to already-existing scaffolding (`routes/api.php`, `bootstrap/app.php` per the 000 audit — no new package).

- [ ] T001 Create `app/Http/Controllers/Api/V1/` directory and `app/Http/Resources/` directory (if `Http/Resources` does not already exist) per plan.md's Project Structure
- [ ] T002 Add the `v1` route group scaffold to `routes/api.php` (`Route::prefix('v1')->name('api.v1.')->group(function (): void { ... })`), empty until Phase 3+ tasks register routes into it
- [ ] T003 [P] Create `tests/Feature/Api/V1/` directory for the parity/contract test suite

**Checkpoint**: `v1` route namespace exists and is wired; no endpoints registered yet.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Shared Resources and route-model-binding scaffolding every endpoint controller depends on. No new privacy or traversal logic — this phase only wires existing engine pieces (`PersonPrivacy`, `DescendantsQueryInterface`, `AncestorsQueryInterface`, `Person::scopeSearch()`, `Lineage::scopeSearch()`) into a serialization layer, per FR-006 and Constitution Principle VI.

**⚠️ CRITICAL**: No user story controller/route work can begin until this phase is complete.

- [ ] T003a Verify prerequisites before any Resource is written: confirm `app/Support/PersonPrivacy.php` (spec 003, extended by spec 007), `app/Models/Lineage.php` + `Lineage::scopeSearch()` (specs 001/006), and `Person::scopeSearch()` (spec 006) all exist and are implemented (not just specced) in the codebase. If any is missing, STOP — do not inline a substitute privacy/search/traversal implementation in this feature's Resources/controllers as a stopgap (violates FR-006/Principle VI); implement the missing prerequisite spec first, or explicitly pause 010 until it lands.
- [ ] T004 [P] Create `PersonResource` in `app/Http/Resources/PersonResource.php`, wrapping `PersonPrivacy::publicFields($this->resource)` (spec 003/007) verbatim — no new field logic, no new privacy branching
- [ ] T005 [P] Create `LineageResource` in `app/Http/Resources/LineageResource.php` per data-model.md's shape (`id`, `name`, `slug`, `description`, `member_count`)
- [ ] T006 Create `SearchResultResource` in `app/Http/Resources/SearchResultResource.php`, wrapping paginated `people`/`lineages` collections through `PersonResource`/`LineageResource` per data-model.md (depends on T004, T005)
- [ ] T007 [P] Add a 404 JSON response (no enumeration hint, matching spec 003 FR-007) for missing/soft-deleted `{person}`/`{lineage}` route-model bindings under the `api` middleware group in `bootstrap/app.php`'s exception handling for `Illuminate\Database\Eloquent\ModelNotFoundException`

**Checkpoint**: Resources exist and compile; foundation ready for controller/test work in every user story.

---

## Phase 3: User Story 1 - Fetch a person's public data via API (Priority: P1) 🎯 MVP

**Goal**: `GET /api/v1/persons/{person}` returns exactly the same privacy-filtered fields as the public web profile (spec 003), for both a deceased person and a living non-opted-in person.

**Independent Test**: Request a deceased seeded person via the API and diff the JSON against the same person's public web profile output; request a living, non-opted-in person and verify sensitive fields are absent (not null) from the JSON exactly as they're absent from the web profile.

### Tests for User Story 1 ⚠️

- [ ] T008 [US1] Write `tests/Feature/Api/V1/PersonEndpointMatchesPublicProfileTest.php`: asserts `GET /api/v1/persons/{person}` JSON equals `PersonResource::make($person)->resolve()` (or the equivalent web-profile-rendered transform of `PersonPrivacy::publicFields()`) for (a) a deceased person with full data and (b) a living, non-opted-in person — for case (b), assert sensitive keys are absent from the JSON (`array_key_exists` false), not present-with-null. Run and confirm it FAILS (route/controller do not exist yet).

### Implementation for User Story 1

- [ ] T009 [US1] Create `app/Http/Controllers/Api/V1/PersonController.php` with a single `show(Person $person): PersonResource` action that returns `PersonResource::make($person)` — no privacy/traversal logic of its own (FR-006)
- [ ] T010 [US1] Register `Route::get('/persons/{person}', [PersonController::class, 'show'])->name('persons.show');` inside the `v1` group in `routes/api.php` (depends on T002, T009)
- [ ] T011 [US1] Run `vendor/bin/sail artisan test --compact --filter=PersonEndpointMatchesPublicProfileTest` and confirm it now passes

**Checkpoint**: User Story 1 is fully functional and independently testable — MVP deliverable.

---

## Phase 4: User Story 2 - Fetch descendants/ancestors via API (Priority: P1)

**Goal**: `GET /api/v1/persons/{person}/descendants` and `/ancestors` return the same generation-bounded, privacy-filtered data set as the web descendant/ancestor explorers (specs 004/005), driven by `max_depth`.

**Independent Test**: Request descendants for a person with 3 generations recorded, with `max_depth=2`, and verify the response matches the same data the descendant explorer would show for that limit; repeat for ancestors.

### Tests for User Story 2 ⚠️

- [ ] T012 [P] [US2] Write `tests/Feature/Api/V1/DescendantsEndpointMatchesExplorerTest.php`: asserts `GET /api/v1/persons/{person}/descendants?max_depth=N` JSON matches a `PersonResource`-wrapped transform of the exact same `DescendantsQueryInterface` result set the web descendant explorer (spec 004) renders for the same person and `max_depth`, including the `degree` field per row and privacy filtering per person. Confirm it FAILS first.
- [ ] T013 [P] [US2] Write `tests/Feature/Api/V1/AncestorsEndpointMatchesExplorerTest.php`: same parity structure as T012 against `AncestorsQueryInterface` and the web ancestor explorer (spec 005). Confirm it FAILS first.

### Implementation for User Story 2

- [ ] T014 [P] [US2] Create `app/Http/Controllers/Api/V1/DescendantsController.php` with `index(Request $request, Person $person, DescendantsQueryInterface $query): AnonymousResourceCollection`, calling the injected `DescendantsQueryInterface` with `max_depth` from the request (default matching spec 004's web default) and paginating per FR-007, mapping rows through `PersonResource` with `degree` preserved — no reimplemented traversal (Constitution Principle VI)
- [ ] T015 [P] [US2] Create `app/Http/Controllers/Api/V1/AncestorsController.php` mirroring T014 against `AncestorsQueryInterface` and spec 005's default `max_depth`
- [ ] T016 [US2] Register `Route::get('/persons/{person}/descendants', [DescendantsController::class, 'index'])->name('persons.descendants');` and `Route::get('/persons/{person}/ancestors', [AncestorsController::class, 'index'])->name('persons.ancestors');` inside the `v1` group in `routes/api.php` (depends on T014, T015)
- [ ] T017 [US2] Run `vendor/bin/sail artisan test --compact --filter=DescendantsEndpointMatchesExplorerTest` and `--filter=AncestorsEndpointMatchesExplorerTest`, confirm both now pass

**Checkpoint**: User Stories 1 and 2 both independently functional — the two recursive-traversal endpoints are API-accessible.

---

## Phase 5: User Story 3 - Search and lineage endpoints (Priority: P2)

**Goal**: `GET /api/v1/search` and `GET /api/v1/lineages/{lineage}` + `/members` mirror specs 006 and 001 respectively.

**Independent Test**: Search via `GET /api/v1/search?q=Rakoto` and verify person and lineage results are distinguishable and match spec 006's web search; fetch `GET /api/v1/lineages/{lineage}/members` and verify it matches spec 001's lineage page member list.

### Tests for User Story 3 ⚠️

- [ ] T018 [P] [US3] Write `tests/Feature/Api/V1/SearchEndpointTest.php`: asserts `GET /api/v1/search?q=...` JSON's `people`/`lineages` collections match a `PersonResource`/`LineageResource`-wrapped transform of `Person::scopeSearch()`/`Lineage::scopeSearch()` results, the same data spec 006's web search page renders for the same query, both result types labeled and distinguishable. Confirm it FAILS first.
- [ ] T019 [P] [US3] Write `tests/Feature/Api/V1/LineageMembersEndpointTest.php`: asserts `GET /api/v1/lineages/{lineage}/members` JSON matches a `PersonResource`-wrapped, paginated transform of the same member list spec 001's lineage page renders for that lineage. Confirm it FAILS first.
- [ ] T020 [P] [US3] Write `tests/Feature/Api/V1/ListEndpointsArePaginatedTest.php` (FR-007): asserts `persons/{person}/descendants`, `/ancestors`, `lineages/{lineage}/members`, and `search` all return standard Laravel pagination `meta` (`current_page`, `last_page`, `per_page`, `total`) and enforce a sane per-page cap when no `page`/limit is supplied, rather than unbounded data. Confirm it FAILS first (covers endpoints from US1–US3, placed here as it depends on all endpoints existing).

### Implementation for User Story 3

- [ ] T021 [P] [US3] Create `app/Http/Controllers/Api/V1/LineageController.php` with `show(Lineage $lineage): LineageResource` and `members(Request $request, Lineage $lineage): AnonymousResourceCollection` (paginated, `PersonResource`-wrapped) — reuses the `Lineage` model's existing member relationship, no new query logic
- [ ] T022 [P] [US3] Create `app/Http/Controllers/Api/V1/SearchController.php` with `index(Request $request): SearchResultResource`, calling `Person::scopeSearch()`/`Lineage::scopeSearch()` (spec 006) exactly as the web search page does, paginating both collections per FR-007
- [ ] T023 [US3] Register `Route::get('/lineages/{lineage}', [LineageController::class, 'show'])->name('lineages.show');`, `Route::get('/lineages/{lineage}/members', [LineageController::class, 'members'])->name('lineages.members');`, and `Route::get('/search', [SearchController::class, 'index'])->name('search');` inside the `v1` group in `routes/api.php` (depends on T021, T022)
- [ ] T024 [US3] Run `vendor/bin/sail artisan test --compact --filter=SearchEndpointTest`, `--filter=LineageMembersEndpointTest`, and `--filter=ListEndpointsArePaginatedTest`, confirm all now pass

**Checkpoint**: All six endpoints from contracts/routes.md are implemented and independently functional; read parity with the web surfaces (specs 001, 003, 004, 005, 006) is complete.

---

## Phase 6: Developer-Facing API Docs Page (Full-Stack UI Requirement)

**Purpose**: Deliver the developer-facing `ApiDocsPage` named in spec.md's "UI & Interface Requirements" — required by the Constitution's full-stack specs rule even though this feature's primary surface is an API.

- [ ] T025 [P] Create `app/Livewire/Developer/ApiDocsPage.php` (class-based Livewire component) listing all six endpoints from contracts/routes.md, grouped by resource (Person / Descendants / Ancestors / Lineage / Search), each entry expandable to show route, parameters, an example request, and an example response payload reflecting the privacy-filtered shape (living-person fields absent)
- [ ] T026 [P] Create the accompanying Blade view for `ApiDocsPage`, reusing the existing `developer.*` route group's layout/nav shell (per spec.md, no separate design system for this page)
- [ ] T027 Register `Route::livewire('api-docs', 'developer::api-docs')->name('api-docs');` inside the existing `IsDeveloper`-gated `developer.` route group in `routes/web.php` (depends on T025, T026)
- [ ] T028 Write `tests/Feature/Livewire/Developer/ApiDocsPageTest.php` asserting the page renders and lists all six endpoints from contracts/routes.md for an authenticated developer, and is inaccessible (403/redirect) to a non-developer, per the existing `IsDeveloper` middleware convention

**Checkpoint**: Developer docs page is live under `/developer/api-docs`, listing every endpoint with example payloads.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across all endpoints and code style compliance.

- [ ] T029 Execute quickstart.md's full validation sequence manually (person parity, descendants/ancestors parity, search/lineage endpoints, docs page) against seeded data and confirm each "Expected" outcome
- [ ] T030 Run `vendor/bin/sail artisan test --compact --filter=Api` (full `tests/Feature/Api/V1/*` suite) and confirm all tests pass together
- [ ] T031 Run `vendor/bin/sail bin pint --dirty --format agent` and fix any reported style issues across all files touched in this feature

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately.
- **Foundational (Phase 2)**: Depends on Phase 1 — BLOCKS all user stories (T004–T007 must exist before any controller can return a Resource).
- **User Story 1 (Phase 3)**: Depends on Foundational completion. No dependency on US2/US3.
- **User Story 2 (Phase 4)**: Depends on Foundational completion. Independent of US1/US3 (different controllers/routes/files).
- **User Story 3 (Phase 5)**: Depends on Foundational completion. T020 (pagination test) depends on endpoints from all prior phases existing, so schedule it last within Phase 5.
- **Developer Docs (Phase 6)**: Depends on all six endpoints (Phases 3–5) existing, since the docs page's example payloads reference real routes.
- **Polish (Phase 7)**: Depends on Phases 1–6 all complete.

### Within Each User Story

- Parity test written and confirmed FAILING before the corresponding controller/route is implemented (Constitution Principle V).
- Resource classes (Phase 2) before any controller that returns them.
- Controller before route registration.
- Route registration before running the test to confirm it passes.

### Parallel Opportunities

- T004 and T005 (independent Resource files) in parallel.
- All Setup tasks marked [P] in parallel.
- T012 and T013 (descendants/ancestors tests, different files) in parallel.
- T014 and T015 (descendants/ancestors controllers, different files) in parallel.
- T018, T019, T020 (US3 tests, different files) in parallel.
- T021 and T022 (lineage/search controllers, different files) in parallel.
- T025 and T026 (docs component class and blade view) in parallel.
- Once Foundational (Phase 2) is complete, User Stories 1, 2, and 3 can be staffed and worked in parallel by different developers.

---

## Parallel Example: User Story 2

```bash
# Launch both parity tests for User Story 2 together:
Task: "Write tests/Feature/Api/V1/DescendantsEndpointMatchesExplorerTest.php"
Task: "Write tests/Feature/Api/V1/AncestorsEndpointMatchesExplorerTest.php"

# After both fail as expected, launch both controllers together:
Task: "Create app/Http/Controllers/Api/V1/DescendantsController.php"
Task: "Create app/Http/Controllers/Api/V1/AncestorsController.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup.
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories).
3. Complete Phase 3: User Story 1 (`GET /api/v1/persons/{person}`, parity-tested against the public web profile).
4. **STOP and VALIDATE**: Run T011, confirm parity test passes; person endpoint is a standalone, demoable proof the API layer mirrors privacy rules exactly.
5. Deploy/demo if ready — this is the MVP.

### Incremental Delivery

1. Setup + Foundational → foundation ready.
2. Add User Story 1 → validate independently → MVP demo.
3. Add User Story 2 (descendants/ancestors) → validate independently → demo the recursive-traversal proof.
4. Add User Story 3 (search + lineage) → validate independently → demo full read parity.
5. Add Phase 6 (docs page) → demo the developer-facing surface.
6. Phase 7 polish → ship.

### Parallel Team Strategy

With multiple developers, once Foundational (Phase 2) is done:
- Developer A: User Story 1 (person endpoint).
- Developer B: User Story 2 (descendants/ancestors endpoints).
- Developer C: User Story 3 (search/lineage endpoints).

Each story's controller, route, and test live in distinct files, so no merge conflicts are expected between stories.
