---

description: "Task list template for feature implementation"
---

# Tasks: Global Genealogy Model

**Input**: Design documents from `/specs/002-global-genealogy-model/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: MANDATORY per Constitution Principle V (Test-First, NON-NEGOTIABLE). Every user story phase below has a Tests sub-section written BEFORE the Implementation sub-section; those tests MUST fail before implementation begins.

**Organization**: Tasks are grouped by user story (spec.md priorities P1/P1/P2) to enable independent implementation and testing of each story. This spec modifies the global `team` scope shared by every existing `Person`/`Couple` query, so Phase 2 (Foundational) includes a mandatory full-suite regression gate before any scope change ships.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

Single Laravel monolith project. Source under `app/`, tests under `tests/Feature/`, views under `resources/views/`, per plan.md's Project Structure.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Confirm the working baseline before touching the shared global scope.

- [ ] T001 Run `vendor/bin/sail artisan test --compact --filter=Person` and `vendor/bin/sail artisan test --compact --filter=Couple` to record the current (pre-change) passing baseline for the existing Person/Couple suite; note the exact test count in the PR description for later comparison against SC-001.
- [ ] T002 [P] Review `app/Models/Person.php::booted()` and `app/Models/Couple.php::booted()` and confirm the current `team` global scope predicate matches the "Current behavior" snippet documented in `specs/002-global-genealogy-model/data-model.md` (no drift since the 000 audit).
- [ ] T003 [P] Review `app/Queries/MySqlAncestorsQuery.php`, `app/Queries/MySqlDescendantsQuery.php`, `app/Queries/PgSqlAncestorsQuery.php`, `app/Queries/PgSqlDescendantsQuery.php`, `app/Queries/SQLiteAncestorsQuery.php`, `app/Queries/SQLiteDescendantsQuery.php` and confirm none of them currently apply a `team_id` filter (per FR-002/SC-002, they must stay that way).

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Establish the regression safety net and shared authorization primitive that every user story below depends on. This is the highest-risk phase in the whole roadmap (plan.md Technical Context) because it touches the scope every existing Person/Couple query implicitly trusts.

**⚠️ CRITICAL**: No user story implementation may begin until this phase is complete.

- [ ] T004 Write `tests/Feature/Genealogy/ExistingTeamScopedCrudUnaffectedTest.php`: a Pest feature test that re-runs the core existing team-scoped assertions from `tests/Feature/People/PersonTest.php` and `tests/Feature/Couples/CoupleTest.php` (create/edit/delete a person and a couple inside one team, confirm a contributor from another team cannot see or list them via the default `Person::query()`/`Couple::query()` scope) as a single consolidated regression gate; run it now and confirm it currently PASSES against the unmodified scope (establishes the "before" baseline required by SC-001, not a TDD red step, since the behavior already exists).
- [ ] T005 [P] Write `tests/Feature/Genealogy/DeveloperBypassUnchangedTest.php`: Pest feature test asserting an `is_developer` user still sees and can query every team's `Person`/`Couple` records unfiltered, both before and after the scope change (FR-005). Run it now and confirm it PASSES against current code.
- [ ] T006 [P] Write `tests/Unit/Queries/RecursiveQueriesRemainTeamUnfilteredTest.php`: a Pest unit/feature test asserting the raw SQL/query builder produced by `app/Queries/MySqlAncestorsQuery.php` and `app/Queries/MySqlDescendantsQuery.php` (e.g. via `toSql()`/`getBindings()` or an executed query against seeded cross-team data) contains no `team_id` predicate — i.e. verifies the CTE queries stay unaffected by the scope change (FR-002), rather than gaining a new filter. Run it now and confirm it PASSES against current code.
- [ ] T007 [US3] Create `app/Policies/PersonPolicy.php` via `vendor/bin/sail artisan make:policy PersonPolicy --model=Person --no-interaction`, implementing `update(User $user, Person $person): bool` and `delete(User $user, Person $person): bool` per data-model.md's ownership rule: `true` only if `$person->team_id === $user->currentTeam?->id` or `$user->is_developer`.
- [ ] T008 [US3] Register `PersonPolicy` for the `Person` model (Laravel 12 auto-discovery via model/policy naming convention in `app/Policies/` and `app/Models/`; if auto-discovery does not resolve it, explicitly register it in the relevant service provider) and confirm `Gate::forUser($user)->allows('update', $person)` resolves to `PersonPolicy::update`.

**Checkpoint**: Regression safety net in place, CTE-unaffected guarantee locked in by test, and the single ownership-authorization source of truth (`PersonPolicy`) exists. User story implementation can now begin.

---

## Phase 3: User Story 1 - Two previously separate families connect through a marriage (Priority: P1) 🎯 MVP

**Goal**: A `Couple` and shared child can link people originating in two different teams, and descendant/ancestor traversal crosses that team boundary as one connected graph, without a manual merge step.

**Independent Test**: Create a couple between a RAKOTO-lineage (Team X) person and a RABE-lineage (Team Y) person, add a shared child, and verify the child appears when exploring descendants from either the RAKOTO or the RABE side (spec.md Acceptance Scenario 1).

### Tests for User Story 1 ⚠️

> Write these tests FIRST; confirm they FAIL against the current strict `team_id = current_team` scope before implementing.

- [ ] T009 [P] [US1] Write `tests/Feature/Genealogy/CrossTeamCoupleTraversalTest.php`: Pest feature test creating Person A (Team X), Person B (Team Y), a `Couple` linking them, and a shared child; asserts the child is visible under `Team X`'s contributor via `Person::query()` (default scope) because it is reachable via a couple/parent-child relationship from Team X's own data (FR-003), and likewise visible under Team Y's contributor. Confirm it FAILS on current code (child currently invisible to the opposite team).
- [ ] T010 [P] [US1] Write `tests/Feature/Genealogy/CrossTeamAncestorDescendantQueryTest.php`: Pest feature test seeding the same Team X/Team Y couple + shared child, then calling the descendant query starting from Person A (Team X) and asserting the shared child (and, symmetrically, the ancestor query from the child) returns the full connected chain in a single traversal with no duplicate `Person` rows (SC-002). Confirm it FAILS on current code if the current scope blocks the CTE result set, or passes-but-document if CTEs are already unaffected (per T003/T006) — the assertion that matters is "no duplicate records, full chain returned."

### Implementation for User Story 1

- [ ] T011 [US1] Modify `app/Models/Person.php::booted()`: widen the `team` global scope's `WHERE` clause from a strict `people.team_id = $currentTeam->id` filter to `people.team_id = $currentTeam->id OR people.id IN (<reachable-via-couple-or-parent/child-from-a-person-already-in-current-team>)`, per the predicate shape in `specs/002-global-genealogy-model/data-model.md` (one-hop only, via `couples.person1_id`/`person2_id` and `people.father_id`/`mother_id`/`parents_id`), preserving the existing guest/`is_developer` early-return behavior.
- [ ] T012 [US1] Modify `app/Models/Couple.php::booted()`: apply the symmetric widened predicate to the `team` global scope on `couples.team_id`, so a couple is visible if it belongs to the current team OR either partner is already visible to the current team per the widened `Person` scope.
- [ ] T013 [US1] Run T009 and T010 and confirm both now PASS; run `vendor/bin/sail artisan test --compact --filter=Genealogy` to confirm no other test in the suite regressed.

**Checkpoint**: User Story 1 is fully functional and independently testable — cross-team couples/children are visible and traversable as one connected graph.

---

## Phase 4: User Story 2 - Existing team-scoped editing keeps working (Priority: P1)

**Goal**: A contributor who works only within their own team continues to add, edit, and manage people/couples exactly as before, with zero observable regression from the scope change.

**Independent Test**: Run the existing people-management flows (add person, edit profile, add couple) inside a single team, exactly as before this spec, and confirm all existing Pest tests for these flows still pass (spec.md Independent Test).

### Tests for User Story 2 ⚠️

> These are largely the pre-existing suite plus the T004 consolidated gate; confirm T004 (written in Phase 2) still passes after the Phase 3 scope change before adding anything new here.

- [ ] T014 [US2] Re-run `tests/Feature/Genealogy/ExistingTeamScopedCrudUnaffectedTest.php` (T004) against the widened scope from Phase 3 and confirm it still PASSES unchanged (proves the widened predicate did not leak isolated-team data or break isolated-team CRUD).
- [ ] T015 [P] [US2] Run the full pre-existing suite exactly as named in quickstart.md: `vendor/bin/sail artisan test --compact --filter=Person` and `vendor/bin/sail artisan test --compact --filter=Couple`; compare pass/fail counts against the T001 baseline and confirm 100% pass, unchanged (SC-001).

### Implementation for User Story 2

- [ ] T016 [US2] Audit `app/Http/Controllers/Back/PeopleController.php`'s list/search-oriented actions (e.g. `index`, `search`) and `app/Livewire/Forms/People/PersonForm.php` for any place that assumes the old strict single-team scope (e.g. manually re-applying `where('team_id', ...)` redundantly, or assuming `Person::count()` reflects only the current team); adjust only where the widened scope would change a result set that must stay identical for isolated-team contributors (e.g. list/search views per FR-003, which explicitly stays team-scoped unless the record is relationship-reachable).
- [ ] T017 [US2] If T016 identifies any list/search view that must remain strictly single-team even with the widened default scope (per FR-003's "existing team-scoped list/search views... UNLESS reachable"), add an explicit narrower query constraint at that call site rather than altering the global scope further; document the reasoning inline only if the logic is non-obvious (per CLAUDE.md comment guidance).

**Checkpoint**: User Stories 1 AND 2 both verified independently — cross-team traversal works, and isolated-team workflows are provably unaffected.

---

## Phase 5: User Story 3 - Determine who may edit a person that spans lineages (Priority: P2)

**Goal**: A single, well-defined ownership rule governs who may directly edit a person/couple that is now reachable from multiple teams, replacing any undefined/incidental behavior.

**Independent Test**: Attach one person to lineages owned by two different teams, then attempt an edit from each team's contributor account and verify the permission outcome matches FR-004 (spec.md Independent Test).

### Tests for User Story 3 ⚠️

> Write these tests FIRST; confirm they FAIL before the policy is wired into the edit routes/UI (the `PersonPolicy` class itself was created in Phase 2, but nothing enforces it against the HTTP/Livewire layer yet).

- [ ] T018 [P] [US3] Write `tests/Feature/Genealogy/CrossTeamEditDeniedTest.php`: Pest feature test with Person A owned by Team X, made cross-team-visible to Team Y via a couple/child link (per US1); asserts a Team Y contributor's request to `GET /people/{personA}/edit-profile` and `PUT`/POST equivalents is denied (403) via `PersonPolicy::update`, while a Team X contributor's request succeeds (FR-004, SC-003). Confirm it FAILS before T019 wires the policy check into the controller.
- [ ] T019 [P] [US3] Write `tests/Feature/Livewire/People/PersonShowOwnershipBadgeTest.php`: Livewire/Pest test asserting `back.people.show` (or its underlying component) renders the "Géré par une autre équipe" read-only badge and a disabled/hidden edit affordance plus a "Proposer une modification" CTA when `$person->team_id !== $viewer->currentTeam->id`, and renders the normal full-edit UI with no badge/CTA when they match (spec.md UI & Interface Requirements, Key Screen States). Confirm it FAILS before T020/T021 add the badge markup.

### Implementation for User Story 3

- [ ] T020 [US3] Modify `app/Http/Controllers/Back/PeopleController.php`'s edit actions (`editProfile`, `editFamily`, `editContact`, `editDeath`, `editEvents`, `editFiles`, `editPhotos`, `editPartner`) to call `$this->authorize('update', $person)` (or the existing `abort_unless` pattern replaced with `PersonPolicy`) instead of/alongside the current `hasPermission('person:update')` check, so cross-team edit attempts are denied per FR-004 regardless of the contributor's own-team permission grant.
- [ ] T021 [US3] Modify `resources/views/back/people/show.blade.php` (and any shared partial it includes for the edit-action toolbar) to check `@can('update', $person)`: render the "Géré par une autre équipe" badge and "Proposer une modification" CTA (linking to a placeholder route/anchor pending spec 008, per quickstart.md step 3) when the check fails, and hide/disable edit buttons/pencils in that case; leave the current full-edit UI untouched when the check passes (zero-regression for own-team viewers, per User Story 2).
- [ ] T022 [US3] Run T018 and T019 and confirm both now PASS.

**Checkpoint**: All three user stories independently functional — cross-team traversal (US1), zero-regression own-team editing (US2), and a single enforced ownership rule for cross-team edit attempts (US3).

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across all three user stories before this spec is considered done.

- [ ] T023 Run the full existing Person/Couple regression suite as the shipping gate: `vendor/bin/sail artisan test --compact --filter=Person` and `vendor/bin/sail artisan test --compact --filter=Couple`; confirm 100% pass and the count matches the T001 baseline exactly (SC-001 is a hard gate, not advisory).
- [ ] T024 Run `vendor/bin/sail artisan test --compact --filter=Genealogy` to execute the full new `tests/Feature/Genealogy/*` suite (T004, T005, T006, T009, T010, T018) plus `tests/Feature/Livewire/People/PersonShowOwnershipBadgeTest.php` (T019) together and confirm all pass.
- [ ] T025 Manually walk through `specs/002-global-genealogy-model/quickstart.md` steps 1–4 (cross-team traversal, existing-editing unaffected, cross-team edit permission, developer bypass) against a running `vendor/bin/sail up -d` stack and confirm every "Expected" outcome holds.
- [ ] T026 Run `vendor/bin/sail bin pint --dirty --format agent` and fix any reported style issues in all files touched by this spec (`app/Models/Person.php`, `app/Models/Couple.php`, `app/Policies/PersonPolicy.php`, `app/Http/Controllers/Back/PeopleController.php`, and all new test files).
- [ ] T027 [P] Review `app/Models/Person.php` and `app/Models/Couple.php` for N+1 query risk introduced by the widened scope's subquery/join (per plan.md's Performance Goals: "same query shape, different WHERE predicate"); confirm via `vendor/bin/sail artisan tinker` or a quick EXPLAIN that the widened scope does not add a query per row on list/index views.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — establishes the pre-change baseline (T001) required to prove SC-001 later.
- **Foundational (Phase 2)**: Depends on Setup. BLOCKS all user stories — the regression gate (T004), the CTE-unaffected proof (T006), and the `PersonPolicy` (T007/T008) are prerequisites for every story below.
- **User Story 1 (Phase 3)**: Depends on Foundational. Must land first — it is the scope change itself; US2 and US3 both verify against the scope US1 produces.
- **User Story 2 (Phase 4)**: Depends on Foundational AND on Phase 3's scope change existing (US2's tests specifically verify the widened scope from US1 didn't regress isolated-team behavior) — sequenced after US1, not fully independent of it, despite both being P1.
- **User Story 3 (Phase 5)**: Depends on Foundational (uses `PersonPolicy` from T007/T008) and on Phase 3 (cross-team visibility from US1 is a precondition for a cross-team edit attempt to even reach the policy check).
- **Polish (Phase 6)**: Depends on Phases 3, 4, and 5 all being complete.

### User Story Dependencies

- **User Story 1 (P1)**: No dependency on other stories; is the foundation the others verify against.
- **User Story 2 (P1)**: Verifies against US1's scope change; no new entities of its own, so it cannot be built before US1 lands.
- **User Story 3 (P2)**: Uses the `PersonPolicy` built in Foundational; its cross-team edit-attempt scenario requires US1's cross-team visibility to exist first.

### Within Each User Story

- Tests written and confirmed failing before implementation (Constitution Principle V).
- Model/scope changes before controller/policy wiring before view/UI changes.
- Story's own tests passing before moving to the next priority.

### Parallel Opportunities

- T002 and T003 (Phase 1) run in parallel.
- T005 and T006 (Phase 2) run in parallel after T004.
- T009 and T010 (Phase 3 tests) run in parallel.
- T018 and T019 (Phase 5 tests) run in parallel.
- T015 (Phase 4) and T027 (Phase 6, if reordered earlier) can run in parallel with other read-only verification tasks.

---

## Parallel Example: Foundational Phase

```bash
# After T004 establishes the consolidated regression test:
Task: "Write tests/Feature/Genealogy/DeveloperBypassUnchangedTest.php asserting is_developer bypass is unaffected"
Task: "Write tests/Unit/Queries/RecursiveQueriesRemainTeamUnfilteredTest.php asserting CTE queries stay team_id-unfiltered"
```

## Parallel Example: User Story 1

```bash
# Launch both User Story 1 tests together (must fail before implementation):
Task: "Write tests/Feature/Genealogy/CrossTeamCoupleTraversalTest.php for cross-team couple/child visibility"
Task: "Write tests/Feature/Genealogy/CrossTeamAncestorDescendantQueryTest.php for single-traversal connected chain"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (baseline recorded).
2. Complete Phase 2: Foundational (regression gate, CTE-unaffected proof, `PersonPolicy` — CRITICAL, blocks everything else).
3. Complete Phase 3: User Story 1 (the scope change itself — cross-team couples/children traversable as one graph).
4. **STOP and VALIDATE**: Run T013's Genealogy filter and confirm US1's independent test (spec.md) passes with no regression elsewhere.
5. Demo: two lineages connecting through a marriage, descendant exploration crossing the team boundary transparently.

### Incremental Delivery

1. Setup + Foundational → regression net and ownership policy ready.
2. Add User Story 1 → cross-team traversal works → validate independently (MVP!).
3. Add User Story 2 → prove zero regression for own-team contributors → validate independently.
4. Add User Story 3 → enforce the single ownership rule for cross-team edit attempts, with UI badge/CTA → validate independently.
5. Phase 6 Polish → full regression gate (SC-001), quickstart walkthrough, Pint, N+1 check.

### Parallel Team Strategy

With multiple developers, after Foundational completes:

- Developer A: User Story 1 (scope change) — must land before B and C can fully verify their stories.
- Developer B: User Story 2 test/audit work can be drafted in parallel but its final pass depends on US1 landing.
- Developer C: User Story 3's `PersonPolicy` wiring and UI badge can be drafted in parallel but its cross-team edit-attempt test depends on US1's cross-team visibility existing.

---

## Notes

- [P] tasks = different files, no dependencies.
- [Story] label maps task to specific user story for traceability.
- This spec's highest-risk element (plan.md) is the shared `team` global scope on `Person`/`Couple` — Phase 2's T004/T005/T006 exist specifically to make that risk visible and testable before Phase 3 touches it.
- Constitution Principle VI (Reuse Recursive-Query Engine): T003/T006/T010 exist to prove the CTE queries in `app/Queries/*` are not modified by this spec — they already lack a `team_id` filter and must stay that way.
- Verify tests fail before implementing; commit after each task or logical group; stop at any checkpoint to validate a story independently.
- Avoid: vague tasks, same-file conflicts, cross-story dependencies that break independence beyond the sequencing already noted above (US1 before US2/US3, which is intrinsic to this spec, not incidental).
