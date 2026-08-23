---

description: "Task list for Public Person Profile (003-person-profile)"
---

# Tasks: Public Person Profile

**Input**: Design documents from `/specs/003-person-profile/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md, `.specify/memory/constitution.md`

**Tests**: Mandatory — Constitution Principle V (Test-First NON-NEGOTIABLE). Every implementation task is preceded by a failing Pest test.

**Organization**: Tasks are grouped by user story (spec.md) so each story is independently implementable and testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: US1 / US2 / US3, matching spec.md priorities

## Path Conventions

Single Laravel monolith app. Paths below are exactly as named in plan.md's Project Structure section.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Scaffold the new files this feature touches, via Artisan generators per CLAUDE.md conventions.

- [ ] T001 [P] Create `app/Http/Controllers/Front/PersonProfileController.php` via `vendor/bin/sail artisan make:controller Front/PersonProfileController --no-interaction`
- [ ] T002 [P] Create `app/Support/PersonPrivacy.php` via `vendor/bin/sail artisan make:class Support/PersonPrivacy --no-interaction`
- [ ] T003 [P] Create `app/View/Components/PrivacyBanner.php` and its Blade view via `vendor/bin/sail artisan make:component PrivacyBanner --no-interaction`
- [ ] T004 [P] Create `app/Livewire/People/PublicProfile.php` Livewire component and its Blade view via `vendor/bin/sail artisan make:livewire People/PublicProfile --no-interaction`
- [ ] T005 Add the public route in `routes/web.php`: `Route::get('p/{person}', [App\Http\Controllers\Front\PersonProfileController::class, 'show'])->name('public.people.show')`, placed in the ungated "frontend routes" section (outside the `auth:sanctum` group) — uses the distinct `/p/` URI prefix, NOT `people/{person}`, since that exact URI is already claimed by the existing authenticated `people.show` route and Laravel would only serve whichever route registers first

**Checkpoint**: Scaffolding exists for all new classes/files; nothing wired up yet.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The privacy filter, the shared banner, and not-found handling — every user story depends on these existing first.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [ ] T006 [P] Unit test in `tests/Unit/Support/PersonPrivacyTest.php`: `PersonPrivacy::isLiving()`/`isPubliclyVisible()` return true/false correctly for a person with vs without `dod`/`yod`, and `PersonPrivacy::publicFields()` omits `street`/`number`/`postal_code`/`city`/`province`/`state`/`country`/`phone`/exact `dob` for a living, non-opted-in person while always including `firstname`/`surname`/`birthname`/`nickname`/`photo`/`summary`/lineages/parents/partners/children — write first, confirm it fails
- [ ] T007 [P] Feature test in `tests/Feature/PersonProfile/NotFoundForNonexistentOrDeletedPersonTest.php`: `GET /people/{id}` for a nonexistent ID and for a soft-deleted person both return a styled 404 (not the raw framework error page), with responses that don't let a caller distinguish "does not exist" from "is private" (FR-007) — write first, confirm it fails
- [ ] T008 Implement `PersonPrivacy::isLiving(Person $person): bool` and `PersonPrivacy::isPubliclyVisible(Person $person): bool` in `app/Support/PersonPrivacy.php` as a small static-method class exactly per data-model.md (reuses `Person::isDeceased()`; no opt-in mechanism yet — spec 007 replaces only `isPubliclyVisible()`'s body later without changing call sites) — makes T006 pass
- [ ] T009 Implement `PersonPrivacy::publicFields(Person $person): array` in `app/Support/PersonPrivacy.php` per the data-model.md projection table — makes T006 fully pass
- [ ] T010 [P] Implement `PrivacyBanner` in `app/View/Components/PrivacyBanner.php` + `resources/views/components/privacy-banner.blade.php` as a stateless Blade component (no Livewire) that renders "Profil privé — informations limitées." when its `shown` prop is true and renders nothing otherwise
- [ ] T011 Implement `PersonProfileController::show(Person $person): View` in `app/Http/Controllers/Front/PersonProfileController.php`: resolves the person respecting the default soft-delete scope, returns a styled 404 view for a nonexistent/soft-deleted person (FR-007), otherwise mounts the `PublicProfile` Livewire component — makes T007 pass

**Checkpoint**: Route, privacy helper, banner, and 404 handling all exist and are tested — user story implementation can begin.

---

## Phase 3: User Story 1 - Visitor opens a deceased person's profile (Priority: P1) 🎯 MVP

**Goal**: A signed-out visitor can open a deceased person's public profile and see name, lifespan, photo/placeholder, lineages, parents, partners, and children.

**Independent Test**: As a signed-out browser session, open a deceased person's profile URL directly and verify the page renders with their data, with no redirect to a login page.

### Tests for User Story 1 ⚠️

- [ ] T012 [P] [US1] Feature test in `tests/Feature/PersonProfile/GuestCanViewDeceasedPersonProfileTest.php`: a signed-out visitor `GET`-ing `/people/{id}` for a deceased seeded person with parents, a partner, and children gets a 200 response (no login redirect) showing name, lifespan, lineages, parents, partner(s), and children, each linking to its own profile; also asserts this route is distinct from and does not require the `auth:sanctum` middleware that guards the existing `people.show` route — write first, confirm it fails
- [ ] T013 [P] [US1] Feature test in `tests/Feature/PersonProfile/MissingPhotoShowsPlaceholderTest.php`: a person with no photo renders a neutral placeholder avatar in the response, never a broken `<img>` — write first, confirm it fails

### Implementation for User Story 1

- [ ] T014 [US1] Implement `PublicProfile::mount(Person $person): void` in `app/Livewire/People/PublicProfile.php`, eager-loading parents, couples/partners, children, and lineages to avoid N+1 queries
- [ ] T015 [US1] Build the `PublicProfile` Blade view (`app/Livewire/People/PublicProfile` component's view): header (photo-or-placeholder, name, lifespan), lineage tag row, family panel with parents/partners/children rendered as linkable cards, and a quick-actions bar ("Explorer les descendants" / "Explorer les ascendants") — makes T012 pass
- [ ] T016 [US1] Implement the placeholder-avatar fallback (default silhouette image shown when `$person->photo` is null) in the `PublicProfile` component/view — makes T013 pass
- [ ] T017 [US1] Wire `PersonProfileController::show()` to mount `PublicProfile` for the resolved person, confirming the response is reachable with no authentication
- [ ] T018 [US1] Apply the mobile-first Tailwind layout to the `PublicProfile` view: the family panel collapses to a stacked single-column layout below the `md` breakpoint

**Checkpoint**: User Story 1 is fully functional and independently testable — deceased profiles render publicly.

---

## Phase 4: User Story 2 - Living person's profile is protected (Priority: P1)

**Goal**: A visitor opening a living, non-opted-in person's profile never receives address, phone, or exact birth date, and sees a clear privacy indicator instead.

**Independent Test**: As a signed-out visitor, open the profile of a person with no `dod`/`yod`, and verify address/phone/exact birth date are absent and a "private" indicator is shown instead.

### Tests for User Story 2 ⚠️

- [ ] T019 [P] [US2] Feature test in `tests/Feature/PersonProfile/LivingPersonProfileWithholdsSensitiveFieldsTest.php`: for a living (no `dod`/`yod`) person, address/phone/exact birth date are absent from the raw rendered HTML (0 occurrences — SC-002) and the `PrivacyBanner` is rendered; separately, an authenticated contributor with edit rights on that same person still sees full data on the existing authenticated `people.show` route, unaffected (FR-008) — write first, confirm it fails; this is the test verifying living, non-opted-in people never leak sensitive fields

### Implementation for User Story 2

- [ ] T020 [US2] Update `PublicProfile` (`app/Livewire/People/PublicProfile.php`) to source rendered person data through `PersonPrivacy::publicFields()`/`isPubliclyVisible()` instead of raw `Person` attributes, so sensitive fields never enter the Livewire payload — makes T019 pass
- [ ] T021 [US2] Render `<x-privacy-banner :shown="..." />` at the top of the `PublicProfile` view when `PersonPrivacy::isPubliclyVisible($person)` is false
- [ ] T022 [US2] Show year-only lifespan (never full `dob`/`dod`) for a non-opted-in living person in the `PublicProfile` view, per data-model.md
- [ ] T023 [US2] Add a regression assertion (in T019's test) confirming `Back\PeopleController::show()` / `resources/views/back/people/show.blade.php` are unchanged and still show full data to an authenticated contributor with edit rights (FR-008)

**Checkpoint**: User Stories 1 AND 2 ship together — every public profile is privacy-safe from the moment it exists (no window where a living person's data leaks).

---

## Phase 5: User Story 3 - Navigate from a profile into family and lineages (Priority: P2)

**Goal**: From a person's profile, a visitor can click any parent/child/partner or lineage tag and land on that entity's own page.

**Independent Test**: From a person's profile, click each family member link and each lineage tag, and verify each leads to the correct corresponding page.

### Tests for User Story 3 ⚠️

- [ ] T024 [P] [US3] Feature test in `tests/Feature/PersonProfile/FamilyLinksNavigateToProfilesTest.php`: clicking each parent/partner/child link on a profile lands on that person's own `public.people.show` page; clicking each lineage tag lands on that lineage's `/lineages/{lineage:slug}` page (spec 001) — write first, confirm it fails

### Implementation for User Story 3

- [ ] T025 [US3] Link each parent/partner/child card in the `PublicProfile` view to `route('public.people.show', $relatedPerson)`
- [ ] T026 [US3] Link each lineage tag in the `PublicProfile` view's lineage tag row to that lineage's public page (`/lineages/{lineage:slug}`, spec 001) — makes T024 pass
- [ ] T027 [US3] Render individual empty-state copy per sub-section when data is absent ("Aucun parent connu" / "Aucun partenaire enregistré" / "Aucun enfant enregistré", plus an empty lineages state), each shown per-subsection rather than hiding the section

**Checkpoint**: All user stories are independently functional; the full profile is navigable end to end.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Final validation across all three stories.

- [ ] T028 [P] Walk through `specs/003-person-profile/quickstart.md` steps 1–4 manually against seeded data (deceased profile, living profile, family navigation, not-found)
- [ ] T029 Run `vendor/bin/sail artisan test --compact --filter=PersonProfile` and confirm the full feature test suite passes
- [ ] T030 Run `vendor/bin/sail bin pint --dirty --format agent` and fix any formatting issues in all files touched by this feature
- [ ] T031 [P] Re-verify SC-002 (0% of living persons' address/phone/exact birth date appear in rendered HTML) and SC-003 (any directly linked family member reachable in one click) against the acceptance scenarios in spec.md

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately.
- **Foundational (Phase 2)**: Depends on Setup (T001–T005) — BLOCKS all user stories.
- **User Story 1 (Phase 3)**: Depends on Foundational (Phase 2) completion.
- **User Story 2 (Phase 4)**: Depends on Foundational (Phase 2) completion; in practice builds on the `PublicProfile` view T014/T015 created in Phase 3, since it modifies the same files — treat US1 and US2 as sequential within a single-developer flow even though they are independently testable.
- **User Story 3 (Phase 5)**: Depends on Foundational (Phase 2) and on the `PublicProfile` view existing (Phase 3) — extends the same view file with links.
- **Polish (Phase 6)**: Depends on all three user stories being complete.

### Within Each User Story

- Tests are written and confirmed failing before implementation tasks in the same phase.
- Story complete before moving to next priority.

### Parallel Opportunities

- T001–T004 (Setup scaffolding) can run in parallel — different files.
- T006 and T007 (Foundational tests) can run in parallel — different files.
- T010 (PrivacyBanner) can run in parallel with T008/T009 (PersonPrivacy) — different files.
- T012 and T013 (US1 tests) can run in parallel.
- T028 and T031 (Polish) can run in parallel with each other, after T029/T030.

---

## Parallel Example: User Story 1

```bash
# Launch both User Story 1 tests together:
Task: "Feature test for guest viewing a deceased profile in tests/Feature/PersonProfile/GuestCanViewDeceasedPersonProfileTest.php"
Task: "Feature test for missing-photo placeholder in tests/Feature/PersonProfile/MissingPhotoShowsPlaceholderTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 + User Story 2 together)

1. Complete Phase 1: Setup.
2. Complete Phase 2: Foundational (CRITICAL — blocks all stories).
3. Complete Phase 3: User Story 1.
4. Complete Phase 4: User Story 2 immediately after — per spec.md, US1 and US2 are both P1 and MUST ship in the same increment; shipping US1 alone would open a window where public profiles leak living people's data (Constitution Principle II/VIII).
5. **STOP and VALIDATE**: run quickstart.md steps 1–2 independently.
6. Deploy/demo if ready — this is the MVP.

### Incremental Delivery

1. Setup + Foundational → foundation ready.
2. US1 + US2 together → validate → deploy/demo (MVP, privacy-safe from day one).
3. Add User Story 3 → validate independently → deploy/demo.
4. Polish → final validation across all stories.

## Notes

- [P] tasks touch different files with no dependencies.
- [Story] label maps a task to its user story for traceability.
- Verify each test fails before implementing against it.
- Commit after each task or logical group.
- Stop at any checkpoint to validate a story independently.
