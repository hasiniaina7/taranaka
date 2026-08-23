# Implementation Plan: Public Person Profile

**Branch**: `003-person-profile` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/003-person-profile/spec.md`

## Summary

Add a public, unauthenticated route rendering a privacy-filtered person
profile, reusing existing `Person` relationships (parents, partners,
children, timeline) but through a new read path that never requires
`auth:sanctum` and never renders sensitive fields for a living,
non-opted-in person. Depends on spec 001 (lineage tags) being present in
the schema and spec 007 (formal privacy rule) for the shared
`PrivacyBanner`/filtering logic — until 007 ships, this spec implements the
minimum inline privacy filter itself (Constitution Principle VIII: no
sensitive data public without a rule, even before 007 formally exists).

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Livewire 4, TallStackUI

**Storage**: MySQL 8, read-only against existing `people`, `couples`,
`lineage_person` (spec 001) tables.

**Testing**: Pest feature tests for guest access, privacy filtering, and
family-link navigation; Livewire component test for `PersonProfile`.

**Target Platform**: Existing Docker stack, monolith.

**Project Type**: Web application (monolith).

**Performance Goals**: Profile page renders without a perceptible delay for
a person with a typical family size (≤10 children, ≤5 partners).

**Constraints**: MUST NOT reuse the existing authenticated
`people/{person}/show` route/controller as-is (that route assumes an
authenticated, team-scoped viewer) — needs its own guest-safe read path
that applies privacy filtering server-side, not just hides fields with CSS.

**Scale/Scope**: One new public route, one new Livewire component tree, one
shared privacy-filter concern (minimal version here, formalized in 007).

## Constitution Check

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | PASS (N/A) | Read-only feature. |
| II/VIII. Privacy | **Primary concern of this spec** | FR-003/FR-004 implement the minimum viable version of Principle II/VIII ahead of spec 007's formalization. |
| III. Laravel/Livewire Stack | PASS | Public profile is Livewire/TallStackUI, not a separate frontend. |
| IV. MySQL 8 | PASS | No engine-specific code. |
| V. Test-First | GATE | Privacy-filtering tests (SC-002: 0% sensitive field leakage) are the highest-priority tests in this feature. |
| VI. Reuse Recursive-Query Engine | N/A | Profile shows direct relations only (parents/partners/children), not multi-generation traversal (that's specs 004/005). |
| VII. Teams Repositioned | PASS | Public profile bypasses the `team` scope entirely (guest has no `currentTeam`) — spec.md's Team/Lineage scope interaction note already documents this; this plan adds no new team logic. |
| IX. Migrations | N/A | No migration. |
| Full-stack specs | PASS | UI section in spec.md is authoritative; this plan maps it to files below. |

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/003-person-profile/
├── plan.md
├── research.md
├── data-model.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/
├── Http/Controllers/Front/
│   └── PersonProfileController.php   # NEW — GET /people/{person}, guest-safe
├── Livewire/People/
│   ├── PublicProfile.php + blade     # NEW — PersonProfile component (header, lineages, family panel, quick actions)
├── Support/
│   └── PersonPrivacy.php             # NEW — minimal privacy filter helper (isLiving(), publicFields()); superseded/extended by spec 007's formal rule, but must exist now per Constitution Principle VIII
├── View/Components/
│   └── PrivacyBanner.php + blade     # NEW — shared component (also consumed by specs 004/005/006/007)

routes/
├── web.php   # add `Route::get('people/{person}', [PersonProfileController::class, 'show'])->name('public.people.show')` OUTSIDE the auth:sanctum group

tests/Feature/PersonProfile/
├── GuestCanViewDeceasedPersonProfileTest.php
├── LivingPersonProfileWithholdsSensitiveFieldsTest.php
├── MissingPhotoShowsPlaceholderTest.php
├── FamilyLinksNavigateToProfilesTest.php
└── NotFoundForNonexistentOrDeletedPersonTest.php
```

**Structure Decision**: A dedicated `Front\PersonProfileController` +
`PublicProfile` Livewire component, intentionally separate from the
existing authenticated `Back\PeopleController@show` — merging them would
risk the privacy filter being bypassed by a code path that assumes an
authenticated, permissioned viewer (this is the exact kind of drift
Constitution Principle VIII exists to prevent).

## Complexity Tracking

*No Constitution Check violations — table intentionally empty.*
