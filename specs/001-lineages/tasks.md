---

description: "Task list template for feature implementation"
---

# Tasks: Lineages

**Input**: Design documents from `/specs/001-lineages/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: Tests are MANDATORY for this project (Constitution Principle V — Test-First, NON-NEGOTIABLE). Every user story phase below writes failing Pest tests before implementation.

**Organization**: Tasks are grouped by user story (US1, US2, US3) to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

Single Laravel monolith (existing project layout, per plan.md's Project Structure). No `backend/`/`frontend/` split.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Scaffold the files this feature needs before any schema or business logic is written.

- [ ] T001 Run `vendor/bin/sail artisan make:migration create_lineages_table --create=lineages` to scaffold `database/migrations/xxxx_create_lineages_table.php`
- [ ] T002 Run `vendor/bin/sail artisan make:migration create_lineage_person_table --create=lineage_person` to scaffold `database/migrations/xxxx_create_lineage_person_table.php`
- [ ] T003 [P] Run `vendor/bin/sail artisan make:model Lineage -f` to scaffold `app/Models/Lineage.php` and `database/factories/LineageFactory.php`
- [ ] T004 [P] Run `vendor/bin/sail artisan make:model LineageMembership` to scaffold `app/Models/LineageMembership.php` (pivot model)
- [ ] T005 [P] Run `vendor/bin/sail artisan make:request LineageRequest` to scaffold `app/Http/Requests/LineageRequest.php`

**Checkpoint**: Bare files exist; no logic yet.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Schema and base models shared by every user story. No user story can be implemented until this phase is complete.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [ ] T006 Define `lineages` table schema in `database/migrations/xxxx_create_lineages_table.php`: `id`, `name` string(150) indexed, `slug` string(170) unique, `description` text nullable, `origin` string(150) nullable, `cover_image` string nullable, `status` string default `active`, timestamps — per data-model.md Entity: Lineage
- [ ] T007 Define `lineage_person` pivot table schema in `database/migrations/xxxx_create_lineage_person_table.php`: `id`, `lineage_id` FK → `lineages.id` cascade on delete, `person_id` FK → `people.id` cascade on delete, timestamps, unique composite index on (`lineage_id`, `person_id`) — per data-model.md Entity: LineageMembership (depends on T001, T002)
- [ ] T008 Run `vendor/bin/sail artisan migrate` to apply T006/T007 (depends on T006, T007)
- [ ] T009 Implement `app/Models/Lineage.php`: fillable attributes (`name`, `slug`, `description`, `origin`, `cover_image`, `status`), `people()` → `belongsToMany(Person::class, 'lineage_person')->using(LineageMembership::class)->withTimestamps()`, `isDeletable(): bool` (mirrors `Team::isDeletable()`/`Person::isDeletable()` — `false` if `$this->people()->exists()`), auto-slug generation on create with numeric-suffix collision handling (`rakoto`, `rakoto-2`, ...), `LogsActivity` trait + `getActivitylogOptions()` matching the existing `Person`/`Couple`/`Team` pattern (depends on T008)
- [ ] T010 [P] Implement `app/Models/LineageMembership.php` as the pivot model for the `lineage_person` table (`lineage_id`, `person_id` fillable, `belongsTo(Lineage::class)`, `belongsTo(Person::class)`) (depends on T008)
- [ ] T011 [P] Add `lineages(): belongsToMany(Lineage::class, 'lineage_person')->using(LineageMembership::class)->withTimestamps()` relationship to `app/Models/Person.php` (depends on T008)
- [ ] T012 [P] Implement `database/factories/LineageFactory.php` with a default definition (`name`, `description`, `origin`) (depends on T009)
- [ ] T013 [P] Implement `app/Http/Requests/LineageRequest.php`: `name` required string max:150; authorize contributors and above per existing role system (depends on T005)
- [ ] T014 Add public and back-office route groups for lineages in `routes/web.php`: `GET /lineages` (front directory), `GET /lineages/{lineage:slug}` (front show), `GET /back/lineages` (contributor management list), matching the existing route-group conventions already used for `people`/`teams` (depends on T009)

**Checkpoint**: Foundation ready — migrations applied, `Lineage`/`LineageMembership` models and relationships exist, routes are registered. User story implementation can now begin.

---

## Phase 3: User Story 1 - Create and describe a lineage (Priority: P1) 🎯 MVP

**Goal**: A contributor can create a new lineage (name, description, optional origin/region and cover image) and see it appear in the lineage directory with a unique slug; a case-insensitive duplicate-name warning is shown but does not block save.

**Independent Test**: Create a lineage through the UI, verify it appears in the lineage list with the entered name/description, with zero people attached.

### Tests for User Story 1 ⚠️

**Write these tests FIRST, ensure they FAIL before implementation.**

- [ ] T015 [P] [US1] Run `vendor/bin/sail artisan make:test --pest Lineage/CreateLineageTest` and write the feature test in `tests/Feature/Lineage/CreateLineageTest.php`: a contributor creates a lineage with name/description/origin, it is persisted with a generated slug, and appears in `/back/lineages` (FR-001, FR-002)
- [ ] T016 [P] [US1] Run `vendor/bin/sail artisan make:test --pest Lineage/DuplicateLineageNameWarningTest` and write the feature test in `tests/Feature/Lineage/DuplicateLineageNameWarningTest.php`: creating a lineage named "rakoto" when "RAKOTO" already exists shows a duplicate warning but still saves successfully with a slug suffix (`rakoto-2`) (FR-003, Edge Cases)
- [ ] T017 [P] [US1] Run `vendor/bin/sail artisan make:test --pest Lineage/PublicLineageDirectoryTest` and write the feature test in `tests/Feature/Lineage/PublicLineageDirectoryTest.php`: `GET /lineages` lists all lineages, is filterable by name, and paginates; a visitor (no auth) can view it

### Implementation for User Story 1

- [ ] T018 [US1] Run `vendor/bin/sail artisan make:livewire Lineages/Form` to scaffold `app/Livewire/Lineages/Form.php` + `resources/views/livewire/lineages/form.blade.php` (depends on T009, T013)
- [ ] T019 [US1] Implement `LineageForm` in `app/Livewire/Lineages/Form.php` + `resources/views/livewire/lineages/form.blade.php`: create/edit fields (name, description, origin/region, cover image), debounced `wire:model.live` on the name field triggering an inline case-insensitive duplicate-name check (FR-003) that renders a dismissible warning banner above the field without blocking submission, saves via `LineageRequest` validation, redirects to the lineage's public page on success (depends on T018)
- [ ] T020 [US1] Run `vendor/bin/sail artisan make:livewire Lineages/Directory` to scaffold `app/Livewire/Lineages/Directory.php` + `resources/views/livewire/lineages/directory.blade.php` (depends on T009)
- [ ] T021 [US1] Implement `LineageDirectory` in `app/Livewire/Lineages/Directory.php` + `resources/views/livewire/lineages/directory.blade.php`: public paginated TallStackUI table/grid of lineages with a name filter input, skeleton-row loading state, and an explicit empty state when zero lineages exist (depends on T020)
- [ ] T022 [US1] Wire `GET /lineages` (front) to render `LineageDirectory`, and `GET /back/lineages` (management list with create/edit/delete entry points) to render `LineageForm`/list, in `routes/web.php` (depends on T014, T019, T021)
- [ ] T023 [US1] Create `resources/views/livewire/lineages/show.blade.php` header markup reused as the redirect target after create — header (name, description, cover), no member logic yet (placeholder wired to US3's `LineageShow`) is intentionally deferred to Phase 4/US3; ensure `GET /lineages/{lineage:slug}` route (T014) resolves to a minimal `LineageShow` stub so the T019 redirect target exists (depends on T014)

**Checkpoint**: User Story 1 fully functional and independently testable — a lineage can be created, appears in the directory, duplicate names warn without blocking.

---

## Phase 4: User Story 2 - Attach a person to one or more lineages (Priority: P1)

**Goal**: A contributor attaches an existing person to one or more lineages (e.g. by birth and by marriage) without duplicating the `Person` record; detaching one membership leaves the other memberships and the person's biographical data untouched.

**Independent Test**: Attach person "Jean RAKOTO" to lineage "RAKOTO", then also attach him to lineage "RABE". Verify his profile lists both lineages and his `Person` row is unchanged/unique.

### Tests for User Story 2 ⚠️

**Write these tests FIRST, ensure they FAIL before implementation.**

- [ ] T024 [P] [US2] Run `vendor/bin/sail artisan make:test --pest Lineage/AttachPersonToLineageTest` and write the feature test in `tests/Feature/Lineage/AttachPersonToLineageTest.php`: attaching a person to a lineage creates one `lineage_person` row and shows the lineage on the person's profile; attaching the same person to a second lineage adds a second row without creating a second `Person` record (FR-004, FR-005, FR-008); re-attaching an already-attached person is idempotent — no duplicate row, no error (FR-010)
- [ ] T025 [P] [US2] Run `vendor/bin/sail artisan make:test --pest Lineage/DetachPersonFromLineageTest` and write the feature test in `tests/Feature/Lineage/DetachPersonFromLineageTest.php`: detaching a person from one of two lineages removes only that membership row, leaves the other membership intact, and leaves the person's biographical fields unchanged (FR-006)

### Implementation for User Story 2

- [ ] T026 [US2] Run `vendor/bin/sail artisan make:livewire People/PersonLineageManager` to scaffold `app/Livewire/People/PersonLineageManager.php` + `resources/views/livewire/people/person-lineage-manager.blade.php` (depends on T009, T011)
- [ ] T027 [US2] Implement `PersonLineageManager` in `app/Livewire/People/PersonLineageManager.php` + `resources/views/livewire/people/person-lineage-manager.blade.php`: autocomplete/multi-select to attach existing lineages via `$person->lineages()->syncWithoutDetaching()` (idempotent per FR-010), chip/tag list of currently attached lineages each with a "detach" (×) action that opens a confirm step before calling `$person->lineages()->detach()` (depends on T026)
- [ ] T028 [US2] Embed `PersonLineageManager` as a "Lignées" section on the person edit screen (`people/{person}/edit-profile`), per plan.md's Livewire component mapping (depends on T027)
- [ ] T029 [US2] Display the full list of attached lineages on the public person profile view (FR-008) (depends on T011)

**Checkpoint**: User Stories 1 AND 2 both work independently — lineages can be created and people attached to multiple lineages without duplication.

---

## Phase 5: User Story 3 - Browse a lineage's members (Priority: P2)

**Goal**: A contributor or visitor opens a lineage's page and sees the list of people currently attached to it, with an explicit empty state when there are none.

**Independent Test**: Open the "RAKOTO" lineage page and verify every person attached to it (per User Story 2) appears in the member list.

### Tests for User Story 3 ⚠️

**Write these tests FIRST, ensure they FAIL before implementation.**

- [ ] T030 [P] [US3] Write the feature test in `tests/Feature/Lineage/LineageShowMembersTest.php` (created via `vendor/bin/sail artisan make:test --pest Lineage/LineageShowMembersTest`): a lineage with 5 attached people lists all 5 with name and birth/death years on `GET /lineages/{lineage:slug}`; a lineage with zero members shows the empty state ("Aucune personne rattachée pour l'instant") rather than an error (FR-009, Acceptance Scenarios 1–2)
- [ ] T031 [P] [US3] Write the feature test in `tests/Feature/Lineage/LineageDeletionBlockedWithMembersTest.php` (created via `vendor/bin/sail artisan make:test --pest Lineage/LineageDeletionBlockedWithMembersTest`): deleting a lineage with one or more attached people is blocked/disabled with an explanatory message; deleting a lineage with zero members succeeds (FR-007, Edge Cases)

### Implementation for User Story 3

- [ ] T032 [US3] Implement `LineageShow` in `app/Livewire/Lineages/Show.php` + `resources/views/livewire/lineages/show.blade.php` (replacing the T023 stub): header (name, description, cover), paginated member list (name, lifespan, link to profile) fed by `$lineage->people()`, skeleton-row loading state, empty state when zero members ("Aucune personne rattachée pour l'instant") (depends on T023, T009)
- [ ] T033 [US3] Add a delete action to the `/back/lineages` management list (in `app/Livewire/Lineages/Directory.php` or a dedicated management component) that calls `Lineage::isDeletable()`, disabling the delete control with a tooltip/inline message when the lineage still has members, and performing the delete otherwise (FR-007, "Blocked action" screen state) (depends on T009, T022)

**Checkpoint**: All user stories independently functional — lineages can be created, people attached/detached across multiple lineages, and members browsed publicly with deletion correctly guarded.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation and consistency pass across all three user stories.

- [ ] T034 [P] Add PHPDoc array-shape blocks and explicit return types across `app/Models/Lineage.php`, `app/Models/LineageMembership.php`, and the new Livewire components, per CLAUDE.md PHP conventions
- [ ] T035 Run `vendor/bin/sail bin pint --dirty --format agent` to fix formatting on all files touched by this feature
- [ ] T036 Run `vendor/bin/sail artisan test --compact --filter=Lineage` and confirm every test from Phases 3–5 passes
- [ ] T037 Run quickstart.md validation: walk through all 4 sections of `specs/001-lineages/quickstart.md` manually against the running stack (create lineage + duplicate warning, attach person to two lineages + idempotent re-attach, browse members as a visitor, deletion guard) and confirm each "Expected" outcome holds

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately.
- **Foundational (Phase 2)**: Depends on Setup completion — BLOCKS all user stories.
- **User Story 1 (Phase 3)**: Depends on Foundational completion. No dependency on US2/US3.
- **User Story 2 (Phase 4)**: Depends on Foundational completion. Independently testable, though it reuses `Lineage` records most naturally created via US1.
- **User Story 3 (Phase 5)**: Depends on Foundational completion; T032 replaces the US1 stub from T023, and its independent test scenario assumes members were attached (US2), but the `LineageShow` component itself only depends on Foundational + the T023 stub existing.
- **Polish (Phase 6)**: Depends on all desired user stories being complete.

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) — no dependency on other stories.
- **User Story 2 (P1)**: Can start after Foundational (Phase 2) — independently testable; benefits from US1 existing so lineages are available to attach to.
- **User Story 3 (P2)**: Can start after Foundational (Phase 2) and after the T023 stub route exists; independent test requires US2's attach flow to have run first to have members to browse.

### Within Each User Story

- Tests MUST be written and FAIL before implementation.
- Models/migrations before services/components.
- Livewire component scaffolding before component logic.
- Core implementation before route wiring.
- Story complete before moving to next priority.

### Parallel Opportunities

- T003, T004, T005 (Setup scaffolding) can run in parallel.
- T010, T011, T012, T013 (Foundational, after T009) can run in parallel.
- T015, T016, T017 (US1 tests) can run in parallel.
- T024, T025 (US2 tests) can run in parallel.
- T030, T031 (US3 tests) can run in parallel.
- Different user stories (Phase 3, 4, 5) can be worked on in parallel by different developers once Phase 2 is complete, respecting the T023/T032 stub-then-replace sequencing for `LineageShow`.

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Feature test for lineage creation in tests/Feature/Lineage/CreateLineageTest.php"
Task: "Feature test for duplicate-name warning in tests/Feature/Lineage/DuplicateLineageNameWarningTest.php"
Task: "Feature test for public lineage directory in tests/Feature/Lineage/PublicLineageDirectoryTest.php"
```

## Parallel Example: User Story 2

```bash
# Launch all tests for User Story 2 together:
Task: "Feature test for attaching a person to multiple lineages in tests/Feature/Lineage/AttachPersonToLineageTest.php"
Task: "Feature test for detaching a person from a lineage in tests/Feature/Lineage/DetachPersonFromLineageTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup.
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories).
3. Complete Phase 3: User Story 1 — lineages can be created and browsed in a directory.
4. **STOP and VALIDATE**: run `vendor/bin/sail artisan test --compact --filter=Lineage` and manually walk quickstart.md §1.
5. Deploy/demo if ready.

### Incremental Delivery

1. Complete Setup + Foundational → foundation ready (schema, models, routes).
2. Add User Story 1 → test independently → demo lineage creation (MVP!).
3. Add User Story 2 → test independently → demo multi-lineage attach without person duplication (the spec's core structural unlock).
4. Add User Story 3 → test independently → demo public member browsing and the deletion guard.
5. Each story adds value without breaking previous stories.

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together.
2. Once Foundational is done:
   - Developer A: User Story 1 (`LineageForm`, `LineageDirectory`)
   - Developer B: User Story 2 (`PersonLineageManager`)
   - Developer C: User Story 3 (`LineageShow`, deletion guard) — starts once the T023 stub route exists
3. Stories complete and integrate independently.

---

## Notes

- [P] tasks = different files, no dependencies.
- [Story] label maps task to specific user story for traceability.
- Tests are MANDATORY (Constitution Principle V) — write and confirm failing before implementing.
- Run `vendor/bin/sail bin pint --dirty --format agent` before any PHP change is considered finished.
- Commit after each task or logical group.
- Stop at any checkpoint to validate a story independently.
- This feature is purely additive — no existing migration modifies `people`/`couples` columns (Constitution Principle IX is trivially satisfied).
