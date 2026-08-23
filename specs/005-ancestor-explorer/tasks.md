---

description: "Task list for feature 005-ancestor-explorer"
---

# Tasks: Ancestor Explorer

**Input**: Design documents from `/specs/005-ancestor-explorer/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: Included per Constitution Principle V (Test-First for Business Logic, NON-NEGOTIABLE) — every task that adds business logic has a preceding, failing-first Pest test.

**Organization**: Tasks are grouped by user story (US1 tree, US2 list, US3 missing-parent handling) so each can be implemented and demoed independently, mirroring spec 004's split.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- File paths are exact, taken from plan.md's Project Structure section

---

## Phase 1: Setup (Shared Infrastructure)

- [ ] T001 Create directories `app/Http/Controllers/Front/`, `app/Livewire/People/Ancestors/`, and `tests/Feature/AncestorExplorer/` if they do not already exist (via `vendor/bin/sail artisan make:controller Front/AncestorsController --invokable --no-interaction` for the controller skeleton; Livewire component skeletons are created in Phase 2 with `make:livewire`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Verify the reused query engine's team-scoping behavior and stand up the shared Livewire shell that every user story renders into. No user story work may begin until this phase is complete.

- [ ] T002 Read `app/Queries/MySqlAncestorsQuery.php` (and `app/Queries/PgSqlAncestorsQuery.php`, `app/Queries/SQLiteAncestorsQuery.php` if present) and document in `specs/005-ancestor-explorer/research.md` under a new "Verification result" note whether the recursive CTE(s) filter by `team_id` anywhere in their `WHERE` clauses. This is a read-only verification task; its output determines whether T003 is needed.
- [ ] T003 [CONDITIONAL — only if T002 finds a `team_id` predicate in any ancestor CTE] Remove that `team_id` predicate from the affected `app/Queries/*AncestorsQuery.php` file(s), touching only the filter clause and leaving the rest of the recursive CTE structure untouched, per Constitution Principle VI (adapt parameters, do not rewrite the query engine). Skip this task entirely if T002 confirms no such predicate exists (expected outcome, per research.md's stated hypothesis).
- [ ] T004 [P] Add public route `GET /p/{person}/ancestors` bound to a new `App\Http\Controllers\Front\AncestorsController` (invokable) in `routes/web.php`, placed in the existing unauthenticated route group alongside `home`/`about`/`help` (outside the `auth:sanctum` middleware group). MUST use the `/p/` prefix, NOT `people/{person}/ancestors` — that exact URI is already registered by the existing authenticated `people.ancestors` route served by `Back\PeopleController`, and reusing it (even under a different route name) would make one of the two routes permanently unreachable.
- [ ] T005 [P] Implement `App\Http\Controllers\Front\AncestorsController` in `app/Http/Controllers/Front/AncestorsController.php`: resolves the `Person` route-model-bound parameter, applies the same privacy/visibility check used by spec 003's public profile route, and renders a Blade view that mounts the `AncestorExplorer` Livewire component with the person's id
- [ ] T006 [US-shared] Create `AncestorExplorer` Livewire component in `app/Livewire/People/Ancestors/Explorer.php` with its view, mirroring `DescendantExplorer`'s shell (Tree/List tab switch, `personId`, `maxDepth` generation-limit control, `expandedNodeIds`, `view` state) per data-model.md's State/Lifecycle section
- [ ] T007 Regression/parity test `tests/Feature/AncestorExplorer/CrossTeamAncestorsAppearInOneTraversalTest.php`: using a two-team fixture (mirrors spec 002/004), assert that calling `AncestorsQueryInterface::getAncestors()` for a person whose ancestor chain crosses into another team's `people` rows returns those cross-team ancestors in the same single traversal — matching the pattern already proven for descendants in spec 004. Must be written to FAIL first if T003 was needed and not yet applied, then PASS once Phase 2 is complete.

**Checkpoint**: Query engine's cross-team behavior is confirmed and (if needed) corrected; public route and shell component exist. User story implementation can now begin.

---

## Phase 3: User Story 1 - Visualize ancestors as an interactive tree (Priority: P1) 🎯 MVP

**Goal**: A visitor opens `/p/{person}/ancestors` and sees an interactive, progressively-expandable tree rooted at that person.

**Independent Test**: Open the ancestor tree for a person with 3+ generations of recorded ancestors and verify each generation can be expanded/collapsed and every visible node links to that person's profile.

### Tests for User Story 1 ⚠️

- [ ] T008 [P] [US1] Feature test `tests/Feature/AncestorExplorer/GuestCanViewAncestorTreeTest.php`: signed-out visitor requests `/p/{person}/ancestors` for a person with two parents and four grandparents recorded, asserts 200 response, root node present, and parents rendered as the first level
- [ ] T009 [P] [US1] Feature test `tests/Feature/AncestorExplorer/ProgressiveExpandCollapseTest.php`: Livewire test asserts a collapsed branch loads the next generation via a Livewire action (no full page reload) when expanded, and collapses again without re-fetching
- [ ] T010 [P] [US1] Feature test `tests/Feature/AncestorExplorer/GenerationLimitTest.php`: asserts traversal respects a configurable `maxDepth` bound (FR-005) and does not render nodes beyond it
- [ ] T011 [P] [US1] Feature test `tests/Feature/AncestorExplorer/LivingAncestorNodePrivacyTest.php`: asserts a living ancestor (`dod`/`yod` null) appears as a node but withholds sensitive fields, per the same privacy rule as spec 003/004

### Implementation for User Story 1

- [ ] T012 [US1] Create `AncestorTree` Livewire component in `app/Livewire/People/Ancestors/Tree.php` with its view, consuming `AncestorsQueryInterface::getAncestors()`, building the upward tree view-model per data-model.md, and exposing an expand/collapse Livewire action per branch (depends on T006)
- [ ] T013 [US1] Wire node click-through: each rendered node in `Tree`'s Blade view links to `route('people.show', $person)` (spec 003's public profile), satisfying FR-009 (depends on T012)
- [ ] T014 [US1] Apply the living-person privacy rule to tree nodes in `app/Livewire/People/Ancestors/Tree.php`: render the node but withhold sensitive fields when `dod`/`yod` is null, per FR-007 and Constitution Principle II/VIII (depends on T012)
- [ ] T015 [US1] Enforce the `maxDepth` generation-limit control in `AncestorExplorer` (`app/Livewire/People/Ancestors/Explorer.php`) and pass it through to `AncestorTree`, satisfying FR-005 (depends on T006, T012)
- [ ] T016 [US1] Mount `AncestorExplorer` from the `AncestorsController@__invoke` Blade view (`resources/views/front/people/ancestors.blade.php` or equivalent existing front-view convention), completing FR-001 (depends on T005, T006)

**Checkpoint**: User Story 1 is fully functional and independently testable — visitors can open and progressively expand an ancestor tree.

---

## Phase 4: User Story 2 - View ancestors as a filterable list (Priority: P2)

**Goal**: A visitor switches to a flat, generation-filterable list of the same ancestor set.

**Independent Test**: Open the list view for the same person as User Story 1 and confirm every ancestor from the tree appears with a correct generation number, and that filtering by generation narrows the list correctly.

### Tests for User Story 2 ⚠️

- [ ] T017 [P] [US2] Feature test `tests/Feature/AncestorExplorer/AncestorListShowsGenerationTest.php`: Livewire test switches `AncestorExplorer` to list view and asserts every ancestor from the tree fixture appears with the correct generation number (1 = parents, 2 = grandparents, ...)
- [ ] T018 [P] [US2] Feature test `tests/Feature/AncestorExplorer/AncestorListGenerationFilterTest.php`: Livewire test sets a generation filter on `AncestorList` and asserts only ancestors at that generation are shown

### Implementation for User Story 2

- [ ] T019 [US2] Create `AncestorList` Livewire component in `app/Livewire/People/Ancestors/ListView.php` with its view, rendering a TallStackUI table with columns Generation / Name / Lineage / Birth year, sourced from `AncestorsQueryInterface::getAncestors()` (depends on T006)
- [ ] T020 [US2] Add a generation filter (TallStackUI select/input bound via `wire:model`) to `app/Livewire/People/Ancestors/ListView.php`, satisfying FR-004 (depends on T019)
- [ ] T021 [US2] Wire each list row to link to `route('people.show', $person)`, satisfying FR-009 for the list view (depends on T019)
- [ ] T022 [US2] Wire the Tree/List tab in `AncestorExplorer` (`app/Livewire/People/Ancestors/Explorer.php`) to mount `AncestorList` when the `view` state is `list`, completing FR-003 (depends on T006, T019)

**Checkpoint**: User Stories 1 AND 2 both work independently — visitors can toggle between tree and filterable list.

---

## Phase 5: User Story 3 - Handle missing or incomplete ancestor data gracefully (Priority: P2)

**Goal**: A visitor exploring a partially-recorded lineage sees an explicit "unknown parent" placeholder instead of a silently omitted or broken slot.

**Independent Test**: Open the ancestor tree for a person with only one recorded parent and confirm the missing parent is shown as an explicit "unknown" slot, not silently omitted or shown as an error.

### Tests for User Story 3 ⚠️

- [ ] T023 [P] [US3] Feature test `tests/Feature/AncestorExplorer/UnknownParentSlotTest.php`: fixture with only a mother recorded; asserts the father slot renders as a "Parent inconnu" placeholder card and that no synthetic `Person` row is created in the database for it (FR-006)
- [ ] T024 [P] [US3] Feature test `tests/Feature/AncestorExplorer/NoAncestorsRecordedTest.php`: fixture with a root person who has zero recorded ancestors; asserts the tree renders the root alone with an "Aucun ancêtre enregistré." empty state, not an error

### Implementation for User Story 3

- [ ] T025 [US3] In `app/Livewire/People/Ancestors/Tree.php`'s view-model transform, insert a rendering-only placeholder slot for any node at `degree < maxDepth` missing a `father_id` or `mother_id`, per data-model.md's Derived Tree/List view model section — never a database row (depends on T012)
- [ ] T026 [US3] Add the dashed-border "Parent inconnu" placeholder card partial to `app/Livewire/People/Ancestors/Tree.php`'s Blade view, rendered side-by-side with the known parent so the two-parents-per-generation layout stays intact (depends on T025)
- [ ] T027 [US3] Add the "Aucun ancêtre enregistré." empty state to `AncestorExplorer`'s view (`app/Livewire/People/Ancestors/Explorer.php`) for the root-only, zero-ancestors case (depends on T006)

**Checkpoint**: All three user stories are independently functional. Ancestor Explorer matches spec 004's Descendant Explorer in structure and behavior, mirrored upward.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [ ] T028 Run through `specs/005-ancestor-explorer/quickstart.md` end-to-end (tree view, list view + filter, missing-parent slot, cross-team traversal) against a running `vendor/bin/sail up -d` stack and record any deviations
- [ ] T029 [P] Run `vendor/bin/sail bin pint --dirty --format agent` and fix any reported style issues across all files touched in this feature
- [ ] T030 Run `vendor/bin/sail artisan test --compact --filter=AncestorExplorer` and confirm the full suite passes

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Depends on Setup; BLOCKS all user stories (T002's verification result gates T003; T004–T007 gate the shell every story mounts into)
- **User Story 1 (Phase 3)**: Depends on Foundational completion — no dependency on US2/US3
- **User Story 2 (Phase 4)**: Depends on Foundational completion; reuses `AncestorExplorer` from T006 but does not depend on US1's Tree implementation
- **User Story 3 (Phase 5)**: Depends on Foundational completion AND on `AncestorTree` existing (T012 from US1), since the placeholder slot is a transform on the tree view-model
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### Within Each User Story

- Tests are written first and must fail before implementation begins (Constitution Principle V)
- Livewire component creation before wiring behavior into it
- Story complete and checkpoint-verified before moving to the next priority

### Parallel Opportunities

- T004 and T005 can run in parallel with T002 (different files) but T003 (conditional) must complete, if triggered, before T007
- All Phase 3 tests (T008–T011) can run in parallel with each other
- All Phase 4 tests (T017–T018) can run in parallel with each other
- All Phase 5 tests (T023–T024) can run in parallel with each other
- US2 (Phase 4) can be implemented in parallel with US1 (Phase 3) by a second developer once Phase 2 is done, since both only depend on T006, not on each other
- US3 (Phase 5) cannot start implementation until T012 (US1's `AncestorTree`) exists, but its tests (T023–T024) can be drafted in parallel with US1/US2 work

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Feature test GuestCanViewAncestorTreeTest in tests/Feature/AncestorExplorer/GuestCanViewAncestorTreeTest.php"
Task: "Feature test ProgressiveExpandCollapseTest in tests/Feature/AncestorExplorer/ProgressiveExpandCollapseTest.php"
Task: "Feature test GenerationLimitTest in tests/Feature/AncestorExplorer/GenerationLimitTest.php"
Task: "Feature test LivingAncestorNodePrivacyTest in tests/Feature/AncestorExplorer/LivingAncestorNodePrivacyTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (verify team-scoping, stand up public route + `AncestorExplorer` shell) — CRITICAL, blocks all stories
3. Complete Phase 3: User Story 1 (interactive tree)
4. **STOP and VALIDATE**: run quickstart.md section 1, confirm tree expand/collapse and privacy behave correctly
5. Deploy/demo — this alone delivers the MVP ancestor-exploration promise

### Incremental Delivery

1. Setup + Foundational → route and shell ready
2. Add User Story 1 (tree) → validate independently → MVP demo
3. Add User Story 2 (filterable list) → validate independently → demo
4. Add User Story 3 (missing-parent placeholder) → validate independently → demo
5. Polish: quickstart.md full pass + Pint + full filtered test run
