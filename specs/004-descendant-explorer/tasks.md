---

description: "Task list for feature implementation"
---

# Tasks: Descendant Explorer

**Input**: Design documents from `/specs/004-descendant-explorer/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: MANDATORY (Constitution Principle V, Test-First, NON-NEGOTIABLE). Every task list below includes tests written before implementation.

**Organization**: Tasks are grouped by user story (spec.md priorities P1/P2/P3) so each story can be implemented and tested independently.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Maps the task to US1/US2/US3
- File paths are exact, from plan.md's Project Structure section

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Create the empty scaffolding the controller and Livewire components will fill in.

- [ ] T001 Create `app/Http/Controllers/Front/DescendantsController.php` as an empty `final class DescendantsController extends Controller` (namespace `App\Http\Controllers\Front`), no methods yet.
- [ ] T002 [P] Create the `app/Livewire/People/Descendants/` and `resources/views/livewire/people/descendants/` directories (no component logic yet — placeholders for `Explorer`, `Tree`, `ListView`).

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure — controller action, public route, host component, and the regression test that locks in the research.md finding — that MUST be complete before any user story is implemented.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [ ] T003 [P] Regression test in `tests/Feature/DescendantExplorer/CrossTeamDescendantsAppearInOneTraversalTest.php` asserting `App\Queries\MySqlDescendantsQuery::getDescendants()` (existing, unmodified per Constitution Principle VI) returns a shared descendant across the two-team fixture from spec 002's quickstart in a single traversal. This documents and locks in the research.md finding — it is NOT a task to add cross-team filtering to the query.
- [ ] T004 Implement `show(Person $person): View` in `app/Http/Controllers/Front/DescendantsController.php`, resolving `App\Contracts\DescendantsQueryInterface` from the container and rendering the Explorer Livewire wrapper view for the given person.
- [ ] T005 [P] Register the public route in `routes/web.php`: `GET /p/{person}/descendants` outside the `auth:sanctum` middleware group, named `front.people.descendants`, pointing to `DescendantsController::show`. MUST use the `/p/` prefix, NOT `people/{person}/descendants` — that exact URI is already registered by the existing authenticated `people.descendants` route (`routes/web.php:48`, `Back\PeopleController@descendants`) and reusing it (even under a different route name) would make one of the two routes permanently unreachable. Leave the existing authenticated route untouched.
- [ ] T006 Create `app/Livewire/People/Descendants/Explorer.php` + `resources/views/livewire/people/descendants/explorer.blade.php` — `mount(Person $person)`, public `$view = 'tree'` and `$maxDepth` (default 3) properties, a Tree/List tab switch, and the shared root-person header (name, photo) reused from `PersonProfile` (spec 003).
- [ ] T007 [P] Verify `App\Support\PersonPrivacy::isPubliclyVisible()` (`app/Support/PersonPrivacy.php`) and the `PrivacyBanner` Blade component (`app/View/Components/PrivacyBanner.php`, spec 003) are available for reuse by descendant nodes; no new code if already present — this is a blocking prerequisite for the privacy-related tasks in User Story 1.

**Checkpoint**: Foundation ready — user story implementation can now begin.

---

## Phase 3: User Story 1 - Visualize descendants as an interactive tree (Priority: P1) 🎯 MVP

**Goal**: A visitor opens a person's descendant tree, sees the root person with their children as the first level, can expand/collapse branches without a full page reload, and every node links to that person's profile.

**Independent Test**: Open the descendant tree for a person with 3+ generations of recorded descendants and verify each generation can be expanded/collapsed and every visible node links to that person's profile.

### Tests for User Story 1 ⚠️

> **Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T008 [P] [US1] Feature test `tests/Feature/DescendantExplorer/GuestCanViewDescendantTreeTest.php` — a signed-out visitor opening `/p/{person}/descendants` sees the root person and first-level children, with deeper branches collapsed by default.
- [ ] T009 [P] [US1] Feature test `tests/Feature/DescendantExplorer/ProgressiveExpandCollapseTest.php` — clicking a collapsed branch's expand affordance reveals the next generation via a Livewire action, without a full page reload; each node links to `people.profile` for that person.
- [ ] T010 [P] [US1] Feature test `tests/Feature/DescendantExplorer/LivingDescendantNodePrivacyTest.php` — a living descendant node renders through `PrivacyBanner` without private fields, consistent with spec 003 profile behavior.

### Implementation for User Story 1

- [ ] T011 [US1] Create `app/Livewire/People/Descendants/Tree.php` — `mount(Person $person, int $maxDepth)`, fetches descendants via `DescendantsQueryInterface::getDescendants()`, builds a nested structure grouped by `degree` and keyed back to the root via `father_id`/`mother_id`, public `$expandedNodeIds` array.
- [ ] T012 [US1] Create `resources/views/livewire/people/descendants/tree.blade.php` — renders the root node and recursive child branches, with the first 2 generations expanded by default (per plan.md's UX default) and Tailwind styling.
- [ ] T013 [US1] Implement `toggleNode(int $personId): void` in `app/Livewire/People/Descendants/Tree.php` to expand/collapse a branch by updating `$expandedNodeIds` (client-visible reveal of the already-fetched depth-bounded result set — no additional server query, per research.md).
- [ ] T014 [US1] Create `resources/views/livewire/people/descendants/partials/node.blade.php` — a clickable card (name, lifespan) linking to that person's profile route (spec 003), a `PrivacyBanner`-style badge for living nodes, an expand chevron/+ affordance with a small inline spinner scoped to that branch only, and an "Aucun descendant enregistré." empty state when a node has no children.
- [ ] T015 [US1] Wire `app/Livewire/People/Descendants/Explorer.php` and its blade to embed `<livewire:people.descendants.tree>` when `$view === 'tree'`, passing `$person` and `$maxDepth`.

**Checkpoint**: User Story 1 is fully functional and independently testable/demoable (MVP).

---

## Phase 4: User Story 2 - View descendants as a filterable list (Priority: P2)

**Goal**: A visitor switches to a flat list of the same descendants (generation, name, lineage), filterable by generation.

**Independent Test**: Open the list view for the same person as User Story 1 and verify the same set of descendants appears, filterable by generation.

### Tests for User Story 2 ⚠️

> **Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T016 [P] [US2] Feature test `tests/Feature/DescendantExplorer/DescendantListShowsSameSetTest.php` — switching to List view shows every descendant present in the tree, each tagged with a generation number.
- [ ] T017 [P] [US2] Feature test `tests/Feature/DescendantExplorer/DescendantListGenerationFilterTest.php` — filtering the list by a specific generation shows only descendants at that generation.

### Implementation for User Story 2

- [ ] T018 [US2] Create `app/Livewire/People/Descendants/ListView.php` — `mount(Person $person, int $maxDepth)`, reuses `DescendantsQueryInterface::getDescendants()`'s flat collection (same call already used in US1, no query duplication), public `$generationFilter` and `$nameFilter` properties with a filtered computed property.
- [ ] T019 [US2] Create `resources/views/livewire/people/descendants/list-view.blade.php` — TallStackUI table with columns Generation / Name / Lineage / Birth year, a generation filter dropdown (FR-004), a name filter input, each row linking to that person's profile.
- [ ] T020 [US2] Wire `app/Livewire/People/Descendants/Explorer.php` and its blade to embed `<livewire:people.descendants.list-view>` when `$view === 'list'`, and implement the tab switch toggling `$view` between `'tree'` and `'list'`.

**Checkpoint**: User Stories 1 AND 2 both work independently.

---

## Phase 5: User Story 3 - Choose how many generations to explore (Priority: P3)

**Goal**: A visitor bounds the traversal to a maximum number of generations, with a clear indicator when more generations exist beyond the bound.

**Independent Test**: Set generation limit to 2 for a person with 5 known generations of descendants and verify only 2 generations are returned.

### Tests for User Story 3 ⚠️

> **Write this test FIRST, ensure it FAILS before implementation**

- [ ] T021 [P] [US3] Feature test `tests/Feature/DescendantExplorer/GenerationLimitTest.php` — setting the generation-limit control to 2 for a person with 5 known generations of descendants results in only 2 generations being shown, with an indicator that more exist.

### Implementation for User Story 3

- [ ] T022 [US3] Add a generation-limit numeric stepper/dropdown control to `app/Livewire/People/Descendants/Explorer.php` (`$maxDepth` public property, an `updatedMaxDepth()` hook that re-passes the new bound down to `Tree`/`ListView`) and to `resources/views/livewire/people/descendants/explorer.blade.php`.
- [ ] T023 [US3] Add a "D'autres générations existent — augmenter la limite" indicator to `resources/views/livewire/people/descendants/partials/node.blade.php` and `resources/views/livewire/people/descendants/list-view.blade.php`, shown when the last visible generation equals `$maxDepth` (more descendants may exist beyond the bound).

**Checkpoint**: All three user stories are independently functional.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across all user stories.

- [ ] T024 [P] Run the quickstart.md validation steps 1–4 (`specs/004-descendant-explorer/quickstart.md`) manually: tree view, list view with generation filter, generation limit, and cross-team traversal, against a fixture person with 3+ generations plus spec 002's two-team fixture.
- [ ] T025 Run `vendor/bin/sail bin pint --dirty --format agent` across all changed PHP files and fix any reported style issues.
- [ ] T026 Run `vendor/bin/sail artisan test --compact --filter=DescendantExplorer` and confirm the full suite (T003, T008–T010, T016–T017, T021) passes.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately.
- **Foundational (Phase 2)**: Depends on Setup completion — BLOCKS all user stories.
- **User Stories (Phase 3–5)**: All depend on Foundational phase completion.
  - Can proceed in parallel if staffed, or sequentially in priority order (P1 → P2 → P3).
- **Polish (Phase 6)**: Depends on all desired user stories being complete.

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2). No dependency on other stories.
- **User Story 2 (P2)**: Can start after Foundational (Phase 2). Reuses the query call pattern from US1 but is independently testable via its own component (`ListView`).
- **User Story 3 (P3)**: Can start after Foundational (Phase 2). Extends `Explorer`'s `$maxDepth` control already scaffolded in T006; independently testable via `GenerationLimitTest.php`.

### Within Each User Story

- Tests MUST be written and FAIL before implementation (Constitution Principle V).
- Livewire component classes before their Blade views' final wiring.
- `Explorer` embedding (last task in each story) after that story's component is functional.

### Parallel Opportunities

- T002 (Setup) can run parallel to T001.
- T003, T005, T007 (Foundational) can run in parallel — different files, no shared dependency.
- All Tests within a story phase marked [P] can run in parallel.
- Different user stories can be worked on in parallel by different developers once Foundational is complete.

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Feature test in tests/Feature/DescendantExplorer/GuestCanViewDescendantTreeTest.php"
Task: "Feature test in tests/Feature/DescendantExplorer/ProgressiveExpandCollapseTest.php"
Task: "Feature test in tests/Feature/DescendantExplorer/LivingDescendantNodePrivacyTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup.
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories; includes the cross-team regression test T003).
3. Complete Phase 3: User Story 1.
4. **STOP and VALIDATE**: run `vendor/bin/sail artisan test --compact --filter=DescendantExplorer`, then quickstart.md step 1.
5. Deploy/demo the tree view as MVP.

### Incremental Delivery

1. Setup + Foundational → foundation ready, cross-team behavior locked in by a passing regression test.
2. Add User Story 1 → test independently → deploy/demo (MVP!).
3. Add User Story 2 → test independently → deploy/demo.
4. Add User Story 3 → test independently → deploy/demo.
5. Phase 6 Polish once all three stories are complete.

---

## Notes

- [P] tasks = different files, no dependencies.
- [Story] label maps task to specific user story for traceability.
- Constitution Principle VI: `app/Queries/*DescendantsQuery.php` is never modified by this task list — only consumed via `DescendantsQueryInterface`.
- Verify tests fail before implementing.
- Stop at any checkpoint to validate a story independently.
