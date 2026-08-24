---

description: "Task list for feature 012-public-tree-visualization"
---

# Tasks: Public Tree Visualization

**Input**: Design documents from `/specs/012-public-tree-visualization/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, quickstart.md

**Tests**: Mandatory per Constitution Principle V (Test-First, NON-NEGOTIABLE). Every task below that adds business logic has a corresponding test task written and confirmed failing before its implementation task.

**Organization**: Tasks are grouped by user story (spec.md priorities: US1 P1, US2 P1, US3 P2) so each story is independently implementable and testable.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: US1 / US2 / US3 — omitted for Setup, Foundational, and Polish tasks
- File paths below are exact, per plan.md's Project Structure section

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Add the new frontend dependency and its Vite entry point.

- [x] T001 Add `family-chart` to `package.json` and run
      `vendor/bin/sail npm install`
- [x] T002 [P] Create empty `resources/js/family-tree.js` entry point and
      import it from `resources/js/app.js` so Vite bundles it

**Checkpoint**: Dependency installed and bundled; no behavior yet.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Extend both existing payload builders with the two new fields
(`photo_url`, `partner_ids`) and stand up the shared canvas rendering
infrastructure every user story depends on. Per research.md, the two trees'
underlying traversal logic is NOT touched — only these two derived fields
are added to their existing output arrays (Constitution Principle VI).

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [x] T003 [P] Write failing Pest test
      `tests/Feature/PublicTreeVisualization/DescendantTreePayloadIncludesPhotoAndPartnersTest.php`:
      seed a person with a photo on disk and a recorded couple; assert
      `Livewire\People\Descendants\Tree::tree()`'s output includes a correct
      `photo_url` and `partner_ids` for that node. Run
      `vendor/bin/sail artisan test --compact --filter=DescendantTreePayloadIncludesPhotoAndPartnersTest`
      and confirm it FAILS (fields don't exist yet).
- [x] T004 Implement `photo_url` (via the existing
      `Storage::disk('photos')->url(...)` convention already used in
      `resources/views/components/tree-node/descendants.blade.php`) and
      `partner_ids` (via `Person::couples()`) in
      `app/Actions/BuildDescendantNodes.php`'s `execute()` mapping, to make
      T003 pass (depends on T003)
- [x] T005 [P] Write failing Pest test
      `tests/Feature/PublicTreeVisualization/AncestorTreePayloadIncludesPhotoAndPartnersTest.php`:
      same assertions as T003, against
      `Livewire\People\Ancestors\Tree::tree()`'s output
- [x] T006 Implement the same `photo_url`/`partner_ids` fields in
      `app/Livewire/People/Ancestors/Tree.php`'s `loadBranch()`/`buildNode()`,
      to make T005 pass (depends on T005)
- [x] T007 [P] Write failing Pest test
      `tests/Feature/PublicTreeVisualization/LivingNodePhotoSuppressedTest.php`:
      a living, non-opted-in person with a photo on file MUST have
      `photo_url === null` in both trees' payloads, per FR-004/FR-010
- [x] T008 Implement the privacy-suppression check (reuse
      `App\Support\PersonPrivacy`) around the `photo_url` assignment in both
      `BuildDescendantNodes` and `Ancestors\Tree`, to make T007 pass
      (depends on T007)
- [x] T009 Create the shared Blade component
      `resources/views/components/family-tree-canvas.blade.php`: a
      `wire:ignore` container plus an Alpine `x-data` binding that receives
      the tree payload and a root profile-URL template as props
- [x] T010 Implement `resources/js/family-tree.js`: initialize `family-chart`
      inside the Alpine component from T009, with adapter functions that
      convert the extended payload (data-model.md's node shape) into
      `family-chart`'s `{id, data, rels}` input format, wire click-vs-drag
      navigation (FR-011) to each node's `profileUrl`, and re-render when
      the Livewire payload updates after an expand/collapse action

**Checkpoint**: Both payload builders and the shared canvas rendering
infrastructure are tested and working; user story implementation can now
begin.

---

## Phase 3: User Story 1 - Explore a modern, interactive descendant tree (Priority: P1) 🎯 MVP

**Goal**: A visitor on a person's profile opens "Descendants" and sees a
graphical, pannable/zoomable tree instead of static nested HTML.

**Independent Test**: Open the descendant tree for a person with 3+
generations and a recorded couple; verify pan/zoom, branch expand/collapse,
couple pairing, and node-click navigation to the person's profile.

### Tests for User Story 1 ⚠️

- [x] T011 [P] [US1] Feature test
      `tests/Feature/PublicTreeVisualization/DescendantTreeRendersCanvasTest.php`:
      `GET /p/{person}/descendants` for a seeded person response includes
      the `family-tree-canvas` container and an embedded payload containing
      that person's descendants, matching the tree Livewire component's
      output (covers AS1–AS3)

### Implementation for User Story 1

- [x] T012 [US1] Update
      `resources/views/livewire/people/descendants/tree.blade.php` to
      render `<x-family-tree-canvas>` with the descendant payload, replacing
      the `@include('livewire.people.descendants.partials.node', ...)` call
- [x] T013 [US1] Add the descendant-specific branch of the adapter in
      `resources/js/family-tree.js` (children + spouses relationships only)
- [ ] T014 [US1] Manually verify (quickstart.md, User Story 1 section) that
      expanding a collapsed branch still triggers the existing Livewire
      `toggleNode()` round-trip and the canvas reflects the updated payload
      without a full page reload

**Checkpoint**: Descendant tree fully upgraded and independently
demonstrable.

---

## Phase 4: User Story 2 - Explore a modern, interactive ancestor tree (Priority: P1)

**Goal**: The same visual/interaction upgrade applied to the ancestor tree,
so both directions feel consistent.

**Independent Test**: Open the ancestor tree for a person with 3+ known
generations of ancestors; verify identical pan/zoom/expand behavior and
visual language as User Story 1.

### Tests for User Story 2 ⚠️

- [x] T015 [P] [US2] Feature test
      `tests/Feature/PublicTreeVisualization/AncestorTreeRendersCanvasTest.php`:
      `GET /p/{person}/ancestors` response includes the shared canvas
      container and a payload matching the ancestor tree's output

### Implementation for User Story 2

- [x] T016 [US2] Update
      `resources/views/livewire/people/ancestors/tree.blade.php` to render
      `<x-family-tree-canvas>` with the ancestor payload
- [x] T017 [US2] Add the ancestor-specific branch of the adapter in
      `resources/js/family-tree.js` (father/mother relationships, no
      children key)
- [ ] T018 [US2] Manually verify (quickstart.md, User Story 2 section) that
      `toggleBranch()`'s lazy per-branch loading still triggers a canvas
      update identical in feel to User Story 1

**Checkpoint**: Ancestor tree fully upgraded; both tree directions are now
visually and behaviorally consistent.

---

## Phase 5: User Story 3 - Use the tree comfortably on a phone (Priority: P2)

**Goal**: Both trees are usable via touch on a mobile viewport, with no
horizontal page overflow.

**Independent Test**: Open either tree on a mobile-width viewport; verify
touch pan/zoom works and nothing overflows the page horizontally.

### Implementation for User Story 3

- [x] T019 [P] [US3] Add responsive/touch CSS rules to
      `resources/css/app.css` for the `family-tree-canvas` container
      (viewport-width containment, touch-action rules enabling native
      pinch/pan on the canvas without triggering page scroll)
- [ ] T020 [US3] Manually verify (quickstart.md, User Story 3 section) on a
      390×844 emulated viewport for both tree directions

**Checkpoint**: Both trees are mobile-usable; all three user stories are
independently demonstrable.

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Scope-boundary regression guard, formatting, and full
regression pass.

- [x] T021 [P] Add
      `tests/Feature/PublicTreeVisualization/BackOfficeViewsUnaffectedTest.php`
      asserting the existing authenticated back-office routes
      (`people.show`, `people.chart`, `developer.*`, `moderation.*`) still
      render via their original views/components, unmodified by this
      feature (FR-007 regression guard)
- [x] T022 Run `vendor/bin/sail bin pint --dirty --format agent` and fix any
      formatting issues across all PHP files touched by this feature
- [x] T023 Run
      `vendor/bin/sail artisan test --compact --filter=PublicTreeVisualization`
      and then the existing `DescendantExplorer`/`AncestorExplorer` suites,
      confirm zero regressions
- [x] T024 Update `quickstart.md` with any step that diverged during
      implementation

**Checkpoint**: Feature complete, tested, formatted, and documented.

> **Note on T014/T018/T020**: left unchecked — these are manual, in-browser
> verification steps (quickstart.md) that could not be run from this
> headless implementation environment. Everything server-side (payload
> shape, privacy suppression, route rendering, back-office non-regression)
> is covered by the automated Pest suite, which passes in full (288
> passed, 4 pre-existing skips, 0 failed). A human should still walk
> through quickstart.md in a browser before considering the feature
> release-ready.
