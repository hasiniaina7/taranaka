---

description: "Task list for 008-contributions: Contributions and Moderation"
---

# Tasks: Contributions and Moderation

**Input**: Design documents from `/specs/008-contributions/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md, `.specify/memory/constitution.md`

**Tests**: MANDATORY per Constitution Principle V (Test-First, NON-NEGOTIABLE). Every story below writes its tests first and confirms they fail before implementation.

**Organization**: Tasks are grouped by user story (spec.md priorities) so each story is independently implementable and testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: US1 / US2 / US3
- Every task names an exact file path (per plan.md's Project Structure)

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Scaffold the new model, policy, and request classes via Artisan generators (per CLAUDE.md convention — nothing hand-rolled).

- [ ] T001 Run `vendor/bin/sail artisan make:model Contribution -m -f --no-interaction` to scaffold `app/Models/Contribution.php`, its migration under `database/migrations/`, and `database/factories/ContributionFactory.php` (structure only, no logic yet).
- [ ] T002 [P] Run `vendor/bin/sail artisan make:policy ContributionPolicy --no-interaction` to scaffold `app/Policies/ContributionPolicy.php`.
- [ ] T003 [P] Run `vendor/bin/sail artisan make:request ContributionRequest --no-interaction` and `vendor/bin/sail artisan make:request ContributionDecisionRequest --no-interaction` to scaffold `app/Http/Requests/ContributionRequest.php` and `app/Http/Requests/ContributionDecisionRequest.php`.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core `Contribution` schema, model, authorization, and request validation that every user story depends on.

**⚠️ CRITICAL**: No user story work may begin until this phase is complete.

- [ ] T004 Define the `contributions` table schema in the migration created at T001 (`database/migrations/xxxx_create_contributions_table.php`) per data-model.md: `author_id` (FK → users.id, `restrictOnDelete()`), `target_type` (string: `person`, `couple`, `person_new`, `relationship_new`), `target_id` (nullable bigint), `field` (nullable string), `old_value` (nullable text), `new_value` (text, required), `justification` (nullable text), `status` (string, default `pending`), `reviewer_id` (nullable FK → users.id), `reviewed_at` (nullable timestamp), `rejection_reason` (nullable text), timestamps. Run `vendor/bin/sail artisan migrate`.
- [ ] T005 [P] Implement `app/Models/Contribution.php`: `$fillable`, `casts()` method (per Laravel 12 convention — `reviewed_at` as datetime), `author(): BelongsTo` (User), `reviewer(): BelongsTo` (User, nullable), a `target()` accessor resolving `target_type`/`target_id` explicitly (not `morphTo()`, per research.md) — for `person`/`couple` targets MUST resolve via `Person::withoutGlobalScope('team')->find($target_id)` / `Couple::withoutGlobalScope('team')->find($target_id)`, since the target is routinely outside the current viewer's team (data-model.md) — and `isStillApplicable(): bool` computed at render time (data-model.md).
- [ ] T005a [P] Write the unit test in `tests/Unit/Models/ContributionTargetResolvesCrossTeamTest.php` (created via `vendor/bin/sail artisan make:test --pest --unit Models/ContributionTargetResolvesCrossTeamTest`): a `Contribution` targeting a `Person` belonging to a different team than the currently-authenticated user's team resolves via `target()` (non-null), proving the `withoutGlobalScope('team')` bypass works — write first, confirm it fails against a naive `Person::find()` implementation (depends on T004)
- [ ] T006 [P] Implement `app/Http/Requests/ContributionRequest.php`: `new_value` required, `target_type`/`field`/`justification` rules per FR-001, authorize any authenticated user to propose.
- [ ] T007 [P] Implement `app/Http/Requests/ContributionDecisionRequest.php`: `rejection_reason` required when the decision action is reject (FR-006), authorize via `ContributionPolicy`.
- [ ] T008 Implement `app/Policies/ContributionPolicy.php`: `propose` (any registered user), `view` (author or moderator), `decide`/`accept`/`reject` (moderator role only), including the self-review guard from data-model.md (author cannot decide their own contribution). Register the policy if `AuthServiceProvider`/`bootstrap/app.php` requires explicit mapping.
- [ ] T009 [P] Implement `database/factories/ContributionFactory.php` with `pending()`, `accepted()`, and `rejected()` states for use across all story tests.
- [ ] T010 Register route skeleton in `routes/web.php` inside the authenticated middleware group: `GET/POST people/{person}/propose`, `GET contributions`, `GET moderation/contributions`, `GET moderation/contributions/{contribution}` (named routes; Livewire component bindings wired in the story phases below).

**Checkpoint**: Foundation ready — user story implementation can now begin.

---

## Phase 3: User Story 1 - A registered user proposes a correction (Priority: P1) 🎯 MVP

**Goal**: A registered user without direct edit rights on a person can submit a proposed correction to one field; the person's live data stays unchanged until a moderator acts.

**Independent Test**: As a registered user with no edit rights on a given person, submit a correction proposal for one field and verify the person's live data is unchanged until a moderator acts.

### Tests for User Story 1 ⚠️

> Write these tests FIRST, ensure they FAIL before implementation.

- [ ] T011 [P] [US1] Feature test `tests/Feature/Contributions/ProposeCorrectionDoesNotChangeLiveDataTest.php`: submitting a correction proposal records author/timestamp/old/new value and leaves the target person's live data unchanged (FR-001, FR-002).
- [ ] T012 [P] [US1] Feature test `tests/Feature/Contributions/DirectEditDeniedRoutesToProposalTest.php`: a user without edit rights on a person is denied direct edit and routed to the proposal flow instead (FR-003, depends on spec 002's `PersonPolicy`).

### Implementation for User Story 1

- [ ] T013 [US1] Implement `app/Livewire/Contributions/ProposeChange.php` + `resources/views/livewire/contributions/propose-change.blade.php`: inline component pre-filled with the current field value, requires `new_value`, accepts optional `justification`, submits via `ContributionRequest`, creates a `Contribution` row without touching `Person`/`Couple`.
- [ ] T014 [US1] Wire `GET/POST people/{person}/propose` in `routes/web.php` to the `ProposeChange` component (depends on T010).
- [ ] T015 [US1] Add the "Suggérer une correction" pencil affordance to the profile view, shown only for fields the viewer cannot edit directly (per spec 002's ownership rule), gated via `ContributionPolicy::propose` alongside `PersonPolicy` — this is the FR-003 redirect target.
- [ ] T016 [US1] Implement `app/Livewire/Contributions/MyContributions.php` + `resources/views/livewire/contributions/my-contributions.blade.php` for `GET /contributions`: authenticated user's own contributions list with a "En attente" status badge for pending items.
- [ ] T017 [US1] Wire `GET contributions` in `routes/web.php` to the `MyContributions` component.

**Checkpoint**: User Story 1 is fully functional and independently testable.

---

## Phase 4: User Story 2 - A moderator reviews and decides on a proposal (Priority: P1)

**Goal**: A moderator opens the pending queue, reviews a proposal, and either accepts it (applying the change) or rejects it (with a required reason).

**Independent Test**: As a moderator, open the pending queue, accept one proposal, and verify the underlying person's data changes to match; reject a second proposal and verify the person's data is unchanged and the proposal is marked rejected with the given reason.

### Tests for User Story 2 ⚠️

> Write these tests FIRST, ensure they FAIL before implementation.

- [ ] T018 [P] [US2] Feature test `tests/Feature/Contributions/ModeratorAcceptsProposalAppliesChangeTest.php`: accepting a pending proposal updates the target person's data to the proposed value and records moderator + timestamp (FR-005).
- [ ] T019 [P] [US2] Feature test `tests/Feature/Contributions/ModeratorRejectsProposalWithReasonTest.php`: rejecting a proposal with a reason leaves the target person's data unchanged and marks the proposal rejected with moderator, timestamp, and reason — proves a rejected contribution never mutates the target model (FR-006).
- [ ] T020 [P] [US2] Feature test `tests/Feature/Contributions/AcceptedContributionTriggersActivitylogTest.php`: accepting a contribution applies the change through the normal Eloquent save path, so `Spatie\Activitylog` records the change on the target model exactly as a direct edit would — proving the deliberate reuse of the existing audit trail rather than a duplicate one (research.md).
- [ ] T021 [P] [US2] Feature test `tests/Feature/Contributions/ContributionPolicyOnlyModeratorsCanDecideTest.php`: only users with the moderator role can accept/reject a contribution; a non-moderator and the contribution's own author (self-review guard) are both denied (FR-005/FR-006).
- [ ] T022 [P] [US2] Feature test `tests/Feature/Contributions/FullTraceabilityRetainedRegardlessOfStatusTest.php`: accepted and rejected contributions both retain author, timestamps, old/new value, decision, decider, decision timestamp, and reason if rejected (FR-007).
- [ ] T023 [P] [US2] Feature test `tests/Feature/Contributions/AuthorNotifiedOnDecisionTest.php`: the proposal author can see the decision (accepted/rejected, with reason if rejected) on their own contribution (FR-009).

### Implementation for User Story 2

- [ ] T024 [US2] Implement `app/Livewire/Contributions/ModerationQueue.php` + `resources/views/livewire/contributions/moderation-queue.blade.php` for `GET /moderation/contributions`: TallStackUI table (author, target, action type, submitted date, status filter per FR-004), with a distinct "Cible modifiée entre-temps" visual state for items where `Contribution::isStillApplicable()` is false.
- [ ] T025 [US2] Implement `app/Livewire/Contributions/ContributionReview.php` + `resources/views/livewire/contributions/contribution-review.blade.php` for `GET /moderation/contributions/{contribution}`: side-by-side diff of old vs. proposed value; Accept action applies `new_value` to the target via the normal Eloquent update path and sets `reviewer_id`/`reviewed_at`/`status=accepted`; Reject action requires `rejection_reason` before being enabled and sets `status=rejected` — enforced via `ContributionPolicy` and `ContributionDecisionRequest`.
- [ ] T026 [US2] Wire `GET moderation/contributions` and `GET moderation/contributions/{contribution}` in `routes/web.php` to `ModerationQueue`/`ContributionReview`, gated by `ContributionPolicy` (moderator-only).
- [ ] T027 [US2] Update `app/Livewire/Contributions/MyContributions.php` (from T016) to reflect the decision outcome: Accepted (green) or Rejected (red, with reason shown inline) without the user needing to open the item (FR-009).

**Checkpoint**: User Stories 1 and 2 both work independently — the full propose→review→decide loop is functional.

---

## Phase 5: User Story 3 - A contributor proposes a brand-new person or relationship (Priority: P2)

**Goal**: A registered user proposes an entirely new person or a new relationship rather than a correction to an existing field; on acceptance, the new record is created and linked.

**Independent Test**: Propose a new person as a child of an existing person, have a moderator accept it, and verify the new person now exists and is correctly linked as a child.

### Tests for User Story 3 ⚠️

> Write this test FIRST, ensure it FAILS before implementation.

- [ ] T028 [P] [US3] Feature test `tests/Feature/Contributions/ProposeNewPersonAndRelationshipTest.php`: proposing a new child queues the proposal without creating the person record yet; once accepted, the new person is created and linked as a child of the existing person (FR-008).

### Implementation for User Story 3

- [ ] T029 [P] [US3] Implement `app/Livewire/Contributions/ProposeNewPerson.php` + `resources/views/livewire/contributions/propose-new-person.blade.php`: "Proposer un enfant" form reachable from the person's family panel, structurally similar to the existing `PersonForm`, submits `target_type=person_new` with `target_id` null.
- [ ] T030 [P] [US3] Implement `app/Livewire/Contributions/ProposeRelationship.php` + `resources/views/livewire/contributions/propose-relationship.blade.php`: "Proposer une union" form, submits `target_type=relationship_new`.
- [ ] T031 [US3] Extend the Accept action in `app/Livewire/Contributions/ContributionReview.php` (from T025) to handle `person_new`/`relationship_new`: creates the new `Person`/`Couple` record on accept and back-fills `target_id` on the `Contribution`.
- [ ] T032 [US3] Add "Proposer un enfant" / "Proposer une union" entry points to the person's family panel view, wired to `ProposeNewPerson`/`ProposeRelationship`.

**Checkpoint**: All three user stories are independently functional.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across all stories.

- [ ] T033 [P] Run the quickstart.md validation end-to-end (all 4 scenarios: propose correction, moderator review accept/reject, propose new person, traceability/notification) against the local Sail stack.
- [ ] T034 Run `vendor/bin/sail bin pint --dirty --format agent` and fix any formatting issues across all new/changed files.
- [ ] T035 [P] Run `vendor/bin/sail artisan test --compact --filter=Contributions` and confirm the full suite passes.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately.
- **Foundational (Phase 2)**: Depends on Setup completion — BLOCKS all user stories.
- **User Story 1 (Phase 3)**: Depends on Foundational only.
- **User Story 2 (Phase 4)**: Depends on Foundational; consumes contributions created in US1 for its independent test but does not require US1's UI code (uses the factory from T009).
- **User Story 3 (Phase 5)**: Depends on Foundational and on `ContributionReview` (T025, US2) for its Accept-action extension (T031).
- **Polish (Phase 6)**: Depends on all desired user stories being complete.

### User Story Dependencies

- **User Story 1 (P1)**: No dependencies on other stories.
- **User Story 2 (P1)**: Independently testable via factory-seeded contributions; T031 (US3) extends US2's `ContributionReview`, but US2 itself has no dependency on US3.
- **User Story 3 (P2)**: Extends US2's `ContributionReview` (T025) — must follow US2's implementation, though its propose-side components (T029/T030) can be built in parallel with US2.

### Within Each User Story

- Tests MUST be written and FAIL before implementation.
- Model/policy/request (Foundational) before Livewire components.
- Components before routes are wired to them.
- Story complete before moving to the next priority.

### Parallel Opportunities

- T002 and T003 (Setup) can run in parallel.
- T005, T006, T007, T009 (Foundational) can run in parallel once T004's migration exists.
- T011 and T012 (US1 tests) can run in parallel.
- T018–T023 (US2 tests) can all run in parallel.
- T029 and T030 (US3 propose-side components) can run in parallel.
- T033 and T035 (Polish) can run in parallel.

---

## Parallel Example: User Story 2

```bash
# Launch all tests for User Story 2 together:
Task: "Feature test ModeratorAcceptsProposalAppliesChangeTest in tests/Feature/Contributions/ModeratorAcceptsProposalAppliesChangeTest.php"
Task: "Feature test ModeratorRejectsProposalWithReasonTest in tests/Feature/Contributions/ModeratorRejectsProposalWithReasonTest.php"
Task: "Feature test AcceptedContributionTriggersActivitylogTest in tests/Feature/Contributions/AcceptedContributionTriggersActivitylogTest.php"
Task: "Feature test ContributionPolicyOnlyModeratorsCanDecideTest in tests/Feature/Contributions/ContributionPolicyOnlyModeratorsCanDecideTest.php"
Task: "Feature test FullTraceabilityRetainedRegardlessOfStatusTest in tests/Feature/Contributions/FullTraceabilityRetainedRegardlessOfStatusTest.php"
Task: "Feature test AuthorNotifiedOnDecisionTest in tests/Feature/Contributions/AuthorNotifiedOnDecisionTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup.
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories).
3. Complete Phase 3: User Story 1.
4. **STOP and VALIDATE**: submit a correction proposal as a non-owning registered user and confirm the target person's live data is unchanged.
5. Note: User Story 1 alone is not deployable value without User Story 2 (proposals are inert without review) — per spec.md, both P1 stories ship together as the MVP.

### Incremental Delivery

1. Complete Setup + Foundational → foundation ready.
2. Add User Story 1 + User Story 2 together (both P1) → propose→review→decide loop works end-to-end → deploy/demo (MVP!).
3. Add User Story 3 → new person/relationship proposals → deploy/demo.
4. Polish → quickstart validation + Pint + full test suite.

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together.
2. Once Foundational is done:
   - Developer A: User Story 1 (propose-side)
   - Developer B: User Story 2 (moderation-side)
   - Developer C: starts User Story 3's propose-side components (T029/T030) once Foundational lands, then waits on T025 (US2) before T031.
3. Stories complete and integrate as described in Dependencies above.

---

## Notes

- [P] tasks = different files, no dependencies.
- [Story] label maps task to specific user story for traceability.
- FR-002 ("no live-data change before acceptance") is the single most safety-critical assertion in this feature (plan.md Constitution Check) — verified by T011 and T019.
- Verify tests fail before implementing.
- Commit after each task or logical group.
- Stop at each checkpoint to validate the story independently.
