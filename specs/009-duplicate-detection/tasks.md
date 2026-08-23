---

description: "Task list for feature 009-duplicate-detection"
---

# Tasks: Duplicate Detection

**Input**: Design documents from `/specs/009-duplicate-detection/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: Mandatory per Constitution Principle V (Test-First, NON-NEGOTIABLE). Every task below that adds business logic has a corresponding test task written and confirmed failing before its implementation task.

**Organization**: Tasks are grouped by user story (spec.md priorities: US1 P1, US2 P1, US3 P2) so each story is independently implementable and testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: US1 / US2 / US3 — omitted for Setup, Foundational, and Polish tasks
- File paths below are exact, per plan.md's Project Structure section

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Prepare directories/namespaces for the new code; no new dependencies are added (Constitution: no dependency changes without approval; plan.md confirms no new packages).

- [ ] T001 Create `tests/Feature/DuplicateDetection/` directory (empty, ready to receive US1/US2/US3 feature tests)
- [ ] T002 [P] Confirm `app/Support/` namespace autoloads correctly for the new `PersonSimilarityScorer` and `PersonCandidateInput` classes (no new composer.json changes required)

**Checkpoint**: Directories ready; no code yet.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The scoring engine and cross-team candidate retrieval that every user story depends on. Per research.md, retrieval (`Person::scopeSimilarTo()`) and scoring (`PersonSimilarityScorer`) are deliberately separate concerns — the scorer MUST stay a pure function with no `Person` model or database dependency, so `Person::scopeSimilarTo()` remains retrieval-only.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [ ] T003 [P] Create `PersonCandidateInput` value object (`name`, `?int $birthYear`) in `app/Support/PersonCandidateInput.php`
- [ ] T004 Write `PersonSimilarityScorerTest` unit tests in `tests/Unit/PersonSimilarityScorerTest.php` covering: same name + matching birth year scores >= 0.75 (acknowledgment-gate boundary); same name + 20-year birth-year gap scores at the 0.4 low-confidence boundary; same name + 40-year gap scores below 0.4 (not shown); either birth year unknown falls back to `birthYearScore = 0.5`; formula `score = nameScore*0.6 + birthYearScore*0.4` is exercised directly. Run `vendor/bin/sail artisan test --compact --filter=PersonSimilarityScorerTest` and confirm all assertions FAIL (class does not exist yet).
- [ ] T005 Implement `PersonSimilarityScorer` (pure function, `score(PersonCandidateInput $a, PersonCandidateInput $b): float`) in `app/Support/PersonSimilarityScorer.php` per research.md's weighting formula, to make T004 pass (depends on T003, T004)
- [ ] T006 Modify the duplicate-detection call site of `Person::scopeSimilarTo()` to bypass the `team` global scope (`withoutGlobalScope('team')`, same pattern as spec 006 Global Search) in `app/Models/Person.php`, satisfying FR-003 cross-team candidate search, without changing the scope's default team-scoped behavior for its other existing callers

**Checkpoint**: Foundation ready — `PersonSimilarityScorer` and cross-team candidate retrieval are both tested and working; user story implementation can now begin.

---

## Phase 3: User Story 1 - Warn before creating a likely-duplicate person (Priority: P1) 🎯 MVP

**Goal**: As a contributor types a new person's name and birth year, existing close matches are shown with a similarity indicator before save.

**Independent Test**: With "Jean Rakoto, born 1954" already in the database, start creating a new person with the same name and birth year, and verify a duplicate warning appears before save, listing the existing match.

### Tests for User Story 1 ⚠️

- [ ] T007 [P] [US1] Feature test in `tests/Feature/DuplicateDetection/WarningShownForCloseMatchTest.php`: seeding an existing "Jean Rakoto, born 1954" and entering matching name/year in the person-creation form shows the candidate in `DuplicateWarningPanel` with its similarity score, before save
- [ ] T008 [P] [US1] Feature test in `tests/Feature/DuplicateDetection/NoWarningForDistantBirthYearTest.php`: entering a name with no resembling existing person shows no warning and the form behaves exactly as today (SC-002)
- [ ] T009 [P] [US1] Feature test in `tests/Feature/DuplicateDetection/CandidateSearchCrossesTeamsTest.php`: an existing person in a different team than the contributor's current team is still returned as a candidate match (FR-003)

### Implementation for User Story 1

- [ ] T010 [US1] Add a live candidate check on the name/birth-year fields (`wire:model.live.debounce.500ms`), invoking `Person::scopeSimilarTo()` (cross-team, per T006) and ranking results through `PersonSimilarityScorer`, in `app/Livewire/People/PersonForm.php` (depends on T005, T006)
- [ ] T011 [US1] Create `DuplicateWarningPanel` Livewire component in `app/Livewire/People/DuplicateWarningPanel.php`, receiving the ranked candidates from T010 and exposing "no candidates" (hidden), "low-confidence" (0.4–0.75), and "high-confidence" (>= 0.75) states
- [ ] T012 [US1] Create the `DuplicateWarningPanel` view in `app/Livewire/People/duplicate-warning-panel.blade.php`: inline panel (not a blocking modal, per FR-006) below the name/birth fields, each candidate as a compact card (name, lifespan, lineage, similarity score as a percentage/bar per FR-002), low-confidence candidates labelled "Correspondance faible"
- [ ] T013 [US1] Mount `DuplicateWarningPanel` into the person-creation form view and wire it to `PersonForm`'s live candidate check from T010

**Checkpoint**: User Story 1 fully functional and independently testable — warnings show before save, cross-team, with no warning when nothing resembles the new entry.

---

## Phase 4: User Story 2 - Contributor resolves a duplicate warning (Priority: P1)

**Goal**: A contributor facing a duplicate warning either links to the existing person (no new record created) or explicitly confirms a distinct person (creation proceeds, decision recorded).

**Independent Test**: Trigger a duplicate warning, choose "this is the same person," and verify no new person record is created; separately, trigger a warning, choose "this is a different person," and verify creation proceeds.

### Tests for User Story 2 ⚠️

- [ ] T014 [P] [US2] Feature test in `tests/Feature/DuplicateDetection/LinkToExistingPersonInsteadOfCreatingTest.php`: choosing "C'est la même personne → lier" on a candidate creates no new `Person` row and redirects to the existing person's route (`people.show`)
- [ ] T015 [P] [US2] Feature test in `tests/Feature/DuplicateDetection/ConfirmDistinctPersonProceedsTest.php`: choosing "Ce sont des personnes différentes → continuer" then saving creates the new person and records the resolution decision via Activitylog
- [ ] T016 [P] [US2] Feature test in `tests/Feature/DuplicateDetection/AcknowledgmentRequiredForHighConfidenceMatchTest.php`: when a candidate scores >= 0.75, attempting `savePerson` without first clicking the acknowledgment control is blocked (Save button/action disabled) until the contributor explicitly acknowledges — the constitution-mandated proof of the soft-gate

### Implementation for User Story 2

- [ ] T017 [US2] Add the "C'est la même personne → lier" action on each candidate card in `app/Livewire/People/DuplicateWarningPanel.php` and `app/Livewire/People/duplicate-warning-panel.blade.php`, redirecting the contributor to the existing person's route (`route('people.show', $person)`) instead of completing a new-person save
- [ ] T018 [US2] Add the "Ce sont des personnes différentes → continuer" acknowledgment control in `app/Livewire/People/DuplicateWarningPanel.php` and `duplicate-warning-panel.blade.php`, shown only once any high-confidence candidate is present, dispatching an acknowledgment event/state
- [ ] T019 [US2] Soft-gate the Save button in `app/Livewire/People/PersonForm.php`: when any candidate scores >= 0.75, disable save until the T018 acknowledgment event has been received; leave save enabled as-is when only low-confidence or no candidates exist (depends on T010, T018)
- [ ] T020 [US2] Record the resolution decision on person creation — Activitylog entry "created after confirming distinct from candidate #{id} (score {n})" when a high-confidence candidate was acknowledged — in `app/Models/Person.php` / the `savePerson` flow in `app/Livewire/People/PersonForm.php` (FR-005, SC-003)

**Checkpoint**: User Stories 1 AND 2 both work independently — warnings appear, and every warning has a working resolution path with recorded traceability.

---

## Phase 5: User Story 3 - Similarity score reflects multiple signals, not name alone (Priority: P2)

**Goal**: The similarity indicator combines name closeness and birth-year proximity, so same-name people 40 years apart score meaningfully lower than same-name people with matching birth years.

**Independent Test**: Compare two same-name people with very different birth years and verify the similarity score is meaningfully lower than for two same-name people with matching birth years.

### Tests for User Story 3 ⚠️

- [ ] T021 [P] [US3] Feature test in `tests/Feature/DuplicateDetection/ScoreReflectsBirthYearProximityTest.php`: seeding two "Jean Rakoto" records (born 1954 and born 1910, 40 years apart) and creating a third "Jean Rakoto" born 1954 shows the 1954 match with a high score requiring acknowledgment, while the 1910 match is either absent or shown as low-confidence only

### Implementation for User Story 3

- [ ] T022 [US3] Ensure the similarity score is rendered as a percentage/bar per candidate card (not just a pass/fail label) in `app/Livewire/People/duplicate-warning-panel.blade.php`, visually distinguishing high-confidence (warning color, acknowledgment required) from low-confidence ("Correspondance faible", no acknowledgment) states per spec.md's Key Screen States
- [ ] T023 [US3] Verify `app/Livewire/People/PersonForm.php` passes the entered birth year (yob/dob) alongside name fields into the `PersonSimilarityScorer` call from T010, so birth-year proximity is not silently dropped from the live check

**Checkpoint**: All user stories independently functional — score reflects both name and birth-year signals end to end.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Validate the feature end-to-end and finalize code style.

- [ ] T024 Run all four `quickstart.md` scenarios manually against the running app (close-match warning, resolve as same person, resolve as different person, score reflects birth-year proximity)
- [ ] T025 [P] Run `vendor/bin/sail artisan test --compact --filter=PersonSimilarityScorer` and `vendor/bin/sail artisan test --compact --filter=DuplicateDetection`, confirm all pass
- [ ] T026 Run `vendor/bin/sail bin pint --dirty --format agent` and fix any style issues across all modified/new PHP files

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion — BLOCKS all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational completion — no dependency on US2/US3
- **User Story 2 (Phase 4)**: Depends on Foundational completion; builds on `DuplicateWarningPanel` and `PersonForm` created in US1 (T010–T013) since it adds actions to those same components — implement after US1
- **User Story 3 (Phase 5)**: Depends on Foundational completion; builds on `DuplicateWarningPanel` from US1 — implement after US1 (can run alongside/after US2)
- **Polish (Phase 6)**: Depends on all desired user stories being complete

### Within Each User Story

- Tests (T007–T009, T014–T016, T021) MUST be written and FAIL before their corresponding implementation tasks
- Foundational scorer/retrieval before any story implementation
- `PersonForm` live-check (T010) before `DuplicateWarningPanel` mounting (T013)
- `DuplicateWarningPanel` component (T011) before its blade view (T012)
- Story complete before moving to next priority

### Parallel Opportunities

- T001 and T002 (Setup) can run in parallel
- T003 (Foundational) can start immediately in parallel with T001/T002
- T007, T008, T009 (US1 tests) can run in parallel — different files
- T014, T015, T016 (US2 tests) can run in parallel — different files
- T021 (US3 test) can run in parallel with US2 tests once Foundational is done
- T025 and T026 (Polish) can run in parallel

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Feature test in tests/Feature/DuplicateDetection/WarningShownForCloseMatchTest.php"
Task: "Feature test in tests/Feature/DuplicateDetection/NoWarningForDistantBirthYearTest.php"
Task: "Feature test in tests/Feature/DuplicateDetection/CandidateSearchCrossesTeamsTest.php"
```

## Parallel Example: User Story 2

```bash
# Launch all tests for User Story 2 together:
Task: "Feature test in tests/Feature/DuplicateDetection/LinkToExistingPersonInsteadOfCreatingTest.php"
Task: "Feature test in tests/Feature/DuplicateDetection/ConfirmDistinctPersonProceedsTest.php"
Task: "Feature test in tests/Feature/DuplicateDetection/AcknowledgmentRequiredForHighConfidenceMatchTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories; delivers `PersonSimilarityScorer` and cross-team retrieval)
3. Complete Phase 3: User Story 1
4. **STOP and VALIDATE**: Run quickstart.md scenario 1 independently — warning appears before save
5. Deploy/demo if ready (a warning-only MVP, with no resolution path yet, is a legitimate incremental checkpoint even though US2 ships immediately after per its P1 priority)

### Incremental Delivery

1. Complete Setup + Foundational → scoring/retrieval ready
2. Add User Story 1 → warnings appear → validate independently (MVP)
3. Add User Story 2 → warnings become actionable (link or confirm-distinct, with traceability) → validate independently
4. Add User Story 3 → score visibly reflects birth-year proximity, not just name → validate independently
5. Polish: quickstart.md full run + Pint

### Parallel Team Strategy

With multiple developers, after Foundational is done:
- Developer A: User Story 1 (must land first — US2/US3 build on its components)
- Developer B: prepares User Story 2 tests (T014–T016) against the US1 component contracts while Developer A finishes implementation, then wires US2 implementation once US1 lands
- Developer C: prepares User Story 3 test (T021) similarly, then adds the score-visualization task once US1's `DuplicateWarningPanel` exists

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- `PersonSimilarityScorer` (T005) MUST remain a pure function with no `Person`/database dependency — `Person::scopeSimilarTo()` (T006) stays retrieval-only; do not fold scoring logic into the scope
- Verify tests fail before implementing (T004, T007–T009, T014–T016, T021)
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
