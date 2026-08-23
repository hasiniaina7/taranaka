---

description: "Task list for feature 007-privacy: Privacy Rules for Living People"
---

# Tasks: Privacy Rules for Living People

**Input**: Design documents from `/specs/007-privacy/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: Included — Constitution Principle V (Test-First, NON-NEGOTIABLE) mandates a Pest test for every unit of business logic in this feature.

**Organization**: Tasks are grouped by user story (US1/US2/US3) so each can be implemented and tested independently, per plan.md's Project Structure.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: US1, US2, or US3
- File paths are exact, taken from plan.md's Project Structure section

---

## Phase 1: Setup

**Purpose**: Prepare the migration and column that every user story depends on

- [ ] T001 Create migration `database/migrations/xxxx_add_is_publicly_visible_to_people_table.php` adding a nullable-not-required `boolean` column `is_publicly_visible` with `default(false)` to the `people` table, additive-only (Constitution Principle IX — no existing column attributes restated because none are touched), generated via `vendor/bin/sail artisan make:migration add_is_publicly_visible_to_people_table --table=people --no-interaction`
- [ ] T002 Run `vendor/bin/sail artisan migrate` to apply the migration locally

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core `PersonPrivacy` seam, `Person` model wiring, and `PersonPolicy` ability that every user story consumes

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T003 [P] Create `app/Support/PersonPrivacy.php` with `final class PersonPrivacy` containing `isLiving(Person $person): bool` (`return ! $person->isDeceased();`), `isPubliclyVisible(Person $person): bool` (`return ! self::isLiving($person) || (bool) $person->is_publicly_visible;`), and `publicFields(Person $person): array` returning the full field set when `isPubliclyVisible()` is true and the withheld-sensitive-field set (per spec.md FR-003: street/number, postal code, city, province/state, country, phone, exact date of birth) when false — this is the single seam spec 003/004/005/006 call sites already assume
- [ ] T004 [P] Create `app/Policies/PersonPolicy.php` (or extend it if a stub already exists) with `togglePrivacy(User $user, Person $person): bool` that reuses the existing ownership/edit-rights `update()` ability verbatim (`return $this->update($user, $person);`), per data-model.md and spec 002's ownership rule
- [ ] T005 [US1] Add `is_publicly_visible` to the `$fillable` array and to the `casts()` method (as `'boolean'`) in `app/Models/Person.php`
- [ ] T006 [US1] Add a model event/observer on `app/Models/Person.php` (a `saving` hook, or a dedicated observer registered in the model's `booted()` method) that does nothing to `is_publicly_visible` itself when `dod`/`yod` change — it exists only so `PersonPrivacy::isPubliclyVisible()` correctly derives effective visibility from `isDeceased()` OR the stored opt-in column, per data-model.md's explicit rule that the death-date transition must never overwrite `is_publicly_visible`

**Checkpoint**: `PersonPrivacy`, the `Person` column/cast, and `togglePrivacy` policy exist — user story implementation can now begin

---

## Phase 3: User Story 1 - A living person's sensitive data never appears publicly by default (Priority: P1) 🎯 MVP

**Goal**: Every public surface (profile, tree, search) withholds a living, non-opted-in person's sensitive fields by consulting the single `PersonPrivacy` rule.

**Independent Test**: Create a living person with address, phone, and exact birth date filled in; verify none of those three fields appear in the raw HTML response of their profile, any tree containing them, or any search result matching them, as a signed-out visitor.

### Tests for User Story 1 ⚠️

- [ ] T007 [P] [US1] Migration test in `tests/Feature/Privacy/IsPubliclyVisibleColumnMigrationTest.php`: asserts `is_publicly_visible` defaults to `false` for a newly created `Person` and that existing rows created before the column existed are not broken (backfill/default applies, no other `people` column attribute changed), per Constitution Principle IX
- [ ] T008 [P] [US1] Test in `tests/Feature/Privacy/LivingPersonDefaultProtectedAcrossAllSurfacesTest.php`: the shared cross-feature suite from SC-001 — creates a living person with address/phone/exact dob, then asserts none of those three fields appear in the raw response of the spec 003 profile route, a spec 004/005 tree view containing the person, and a spec 006 search result matching the person, as a signed-out visitor
- [ ] T009 [P] [US1] Unit test in `tests/Unit/Support/PersonPrivacyTest.php`: `PersonPrivacy::isLiving()` and `PersonPrivacy::isPubliclyVisible()` return correct booleans for (a) living non-opted-in, (b) living opted-in, (c) deceased via `dod`, (d) deceased via `yod` only

### Implementation for User Story 1

- [ ] T010 [US1] Confirm `PrivacyBanner` (already created by spec 003 as a stateless Blade component: `app/View/Components/PrivacyBanner.php` + `resources/views/components/privacy-banner.blade.php`) needs NO changes — it renders purely from a boolean `shown` prop. Do NOT create a new/Livewire `PrivacyBanner`; T008/T009 already exercise it indirectly through `isPubliclyVisible()`. This task is a verification step, not new component work.
- [ ] T011 [US1] Wire spec 003's profile view to consult `PersonPrivacy::publicFields()`/`isPubliclyVisible()` and pass the result into the existing `<x-privacy-banner :shown="..." />` when a living, non-opted-in person is shown (no per-feature reimplementation, per FR-008)
- [ ] T012 [US1] Wire spec 004/005's tree/node views to consult the same `PersonPrivacy` rule and render the existing `PrivacyBanner` Blade component identically to the profile surface (spec.md Acceptance Scenario 2 — no surface exempt)
- [ ] T013 [US1] Wire spec 006's search result rendering to consult the same `PersonPrivacy` rule so sensitive fields never appear in search output

**Checkpoint**: User Story 1 fully functional and independently testable — living persons' sensitive data is withheld on every public surface

---

## Phase 4: User Story 2 - A living person can opt in to public visibility (Priority: P2)

**Goal**: The person's own account (if any) or an authorized contributor can toggle `is_publicly_visible`, and public surfaces immediately reflect the new state.

**Independent Test**: Mark a living person as publicly visible via the opt-in action, then verify their non-sensitive fields (name, lifespan-in-progress indicator, lineage) become visible while still-sensitive fields (exact address) remain withheld unless individually opted in.

### Tests for User Story 2 ⚠️

- [ ] T014 [P] [US2] Test in `tests/Feature/Privacy/OptInMakesPersonPubliclyVisibleTest.php`: an authorized editor toggles `is_publicly_visible` to `true`; asserts name/lineage become visible publicly while address/phone remain withheld
- [ ] T015 [P] [US2] Test in `tests/Feature/Privacy/OptOutReappliesProtectionTest.php`: an authorized editor reverses the opt-in; asserts default protection is immediately re-applied
- [ ] T016 [P] [US2] Test in `tests/Feature/Privacy/UnauthorizedToggleDeniedTest.php`: a contributor without edit rights on the person attempts `PersonPolicy::togglePrivacy` (directly and via the Livewire component); asserts the action is denied and the control does not render in the component's HTML output

### Implementation for User Story 2

- [ ] T017 [P] [US2] Create `app/Livewire/People/PrivacyToggle.php` + `resources/views/livewire/people/privacy-toggle.blade.php`: a switch "Rendre ce profil public" with a short explanatory line, authorized via `PersonPolicy::togglePrivacy`, that does not render at all for users without edit rights on the person (per spec.md's "Unauthorized toggle attempt" screen state — hidden, not merely disabled)
- [ ] T018 [US2] Mount `PrivacyToggle` on the person edit screen (`edit-profile` or a dedicated "Confidentialité" panel per spec.md), reflecting the toggle's new state immediately on change (optimistic UI, same-request)
- [ ] T019 [US2] Update the toggle's persistence action in `app/Livewire/People/PrivacyToggle.php` to authorize via `PersonPolicy::togglePrivacy` before persisting `is_publicly_visible`, logged through the existing `Spatie\Activitylog` mechanism already attached to `Person` (FR-004 auditability)

**Checkpoint**: User Stories 1 AND 2 both work independently — opt-in/opt-out is authorized, auditable, and immediately reflected

---

## Phase 5: User Story 3 - Death is recorded and the person's visibility updates automatically (Priority: P2)

**Goal**: Recording a death date automatically lifts default protection without a separate publish step; removing/correcting it symmetrically re-applies protection based on the stored `is_publicly_visible` value.

**Independent Test**: Record a death date for a person whose profile was previously private, then verify their profile becomes publicly visible (subject to any explicit opt-out, if one exists) without further action.

### Tests for User Story 3 ⚠️

- [ ] T020 [P] [US3] Test in `tests/Feature/Privacy/DeathDateAutoLiftsProtectionTest.php`: a living, privacy-protected, non-opted-in person has `dod` recorded via the `edit-death` form; asserts `PersonPrivacy::isPubliclyVisible()` becomes `true` on the same request/next view with no separate publish action, and that `is_publicly_visible` itself remains `false` in the database (only the derived/effective visibility changed, per data-model.md)
- [ ] T021 [P] [US3] Test in `tests/Feature/Privacy/DeathDateRemovalReappliesProtectionTest.php`: a person with a recorded `dod` (and `is_publicly_visible` still `false` from before death was recorded) has `dod`/`yod` cleared; asserts `PersonPrivacy::isPubliclyVisible()` reverts to `false` (symmetric reversal) and that this round-trip never flipped the stored `is_publicly_visible` column itself
- [ ] T022 [P] [US3] Test in `tests/Feature/Privacy/DeathDateRemovalPreservesPriorOptInTest.php`: a person who was separately opted in (`is_publicly_visible = true`) before also having a `dod` recorded, then has `dod` cleared; asserts the profile remains publicly visible afterward because the original opt-in choice was never overwritten by the death-date lifecycle

### Implementation for User Story 3

- [ ] T023 [US3] Confirm/finalize the `saving` hook added in T006 on `app/Models/Person.php` requires no additional persisted state for the death-date transition — `PersonPrivacy::isPubliclyVisible()`'s existing `! self::isLiving($person) || (bool) $person->is_publicly_visible` formula already derives the correct effective value from `dod`/`yod` at read time; add a PHPDoc block on the hook explaining why no write to `is_publicly_visible` occurs here (Edge Cases symmetry requirement)
- [ ] T024 [US3] Update the `edit-death` Livewire form/action to trigger, on save, an inline confirmation that the profile is now public when `PersonPrivacy::isPubliclyVisible()` newly evaluates `true` (User Story 3's "no separate publish screen" requirement)
- [ ] T025 [US3] Update `app/Livewire/People/PrivacyToggle.php` (from T017) to render disabled with the note "Ce profil est désormais public — personne décédée" whenever `PersonPrivacy::isLiving($person)` is `false`, per spec.md's "Death recorded" screen state

**Checkpoint**: All three user stories independently functional — default protection, opt-in/opt-out, and automatic death-triggered visibility all work through the single `PersonPrivacy` rule

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across all user stories

- [ ] T026 Run `vendor/bin/sail bin pint --dirty --format agent` and fix any formatting issues in all files touched by this feature
- [ ] T027 Execute the quickstart.md manual validation steps (sections 1–3: default protection, opt-in, death recorded) end-to-end in the running application
- [ ] T028 Run `vendor/bin/sail artisan test --compact --filter=Privacy` and confirm the full `tests/Feature/Privacy/*` and `tests/Unit/Support/PersonPrivacyTest.php` suite passes, matching quickstart.md's "Automated coverage" command

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — can start immediately
- **Foundational (Phase 2)**: Depends on Setup (T001–T002) completion — BLOCKS all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational completion — no dependency on US2/US3
- **User Story 2 (Phase 4)**: Depends on Foundational completion; independently testable, though `PrivacyToggle`'s "disabled when deceased" note (T025) is added in US3
- **User Story 3 (Phase 5)**: Depends on Foundational completion; T025 modifies the `PrivacyToggle` component created in T017 (US2)
- **Polish (Phase 6)**: Depends on all three user stories being complete

### Within Each User Story

- Tests written first, confirmed failing before implementation (Constitution Principle V)
- T017 (PrivacyToggle creation, US2) precedes T025 (PrivacyToggle deceased-state update, US3)
- T006 (Foundational observer/event) precedes T023 (US3 confirmation/documentation of that hook)

### Parallel Opportunities

- T003 and T004 (Foundational) can run in parallel — different files
- T007, T008, T009 (US1 tests) can run in parallel — different files
- T014, T015, T016 (US2 tests) can run in parallel — different files
- T020, T021, T022 (US3 tests) can run in parallel — different files
- T011, T012, T013 (US1 surface wiring) touch different existing views and can run in parallel once T010 exists

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together:
Task: "Migration test in tests/Feature/Privacy/IsPubliclyVisibleColumnMigrationTest.php"
Task: "Cross-surface test in tests/Feature/Privacy/LivingPersonDefaultProtectedAcrossAllSurfacesTest.php"
Task: "Unit test in tests/Unit/Support/PersonPrivacyTest.php"

# Once T010 confirms the existing PrivacyBanner needs no changes, wire the three surfaces in parallel:
Task: "Wire spec 003 profile view to PersonPrivacy"
Task: "Wire spec 004/005 tree views to PersonPrivacy"
Task: "Wire spec 006 search results to PersonPrivacy"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (migration)
2. Complete Phase 2: Foundational (`PersonPrivacy`, `PersonPolicy::togglePrivacy`, `Person` column/cast/observer) — CRITICAL, blocks all stories
3. Complete Phase 3: User Story 1 (default protection across all public surfaces)
4. **STOP and VALIDATE**: run `vendor/bin/sail artisan test --compact --filter=Privacy`, confirm SC-001 (0% leakage) holds
5. This is the MVP: Constitution Principle II/VIII's core safety boundary is now enforced platform-wide, even before opt-in/death-lifecycle exist

### Incremental Delivery

1. Setup + Foundational → foundation ready
2. User Story 1 → validate independently → this alone satisfies the constitution's non-negotiable safety requirement
3. User Story 2 → validate independently → opt-in/opt-out live
4. User Story 3 → validate independently → death lifecycle automatic
5. Polish → quickstart.md full pass + Pint

---

## Notes

- [P] tasks touch different files with no dependencies between them
- [Story] labels map tasks to US1/US2/US3 for independent testability
- Constitution Principle V (NON-NEGOTIABLE): every test must be written and confirmed failing before its implementation task
- `PersonPrivacy::isPubliclyVisible()`'s signature and call sites MUST NOT change from what specs 003/004/005/006 already assume — only its internal implementation (plan.md constraint)
- `is_publicly_visible` itself is never written by the death-date lifecycle (T006/T023) — only the derived/effective visibility from `PersonPrivacy::isPubliclyVisible()` changes, per data-model.md
