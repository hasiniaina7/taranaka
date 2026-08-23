# Implementation Plan: Lineages

**Branch**: `001-lineages` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-lineages/spec.md`

## Summary

Introduce a `Lineage` entity and a `LineageMembership` pivot so a `Person`
can be attached to any number of family lines without duplicating the
person record. Add contributor CRUD for lineages, a public lineage
directory/page, and an attach/detach widget on the person edit screen. This
is purely additive to the existing schema — no existing column is modified,
per Constitution Principle IX.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Livewire 4, TallStackUI, Laravel
Jetstream (Team, unchanged), Spatie Activitylog (reuse existing pattern for
`Lineage`/`LineageMembership`), Pest 4

**Storage**: MySQL 8 (reference); no PgSQL/SQLite-specific code required —
this feature adds plain tables/pivot, no recursive queries.

**Testing**: Pest feature tests (Livewire component tests via
`Livewire::test()`) and unit tests for `Lineage`/`LineageMembership` model
behavior (uniqueness, `isDeletable()`).

**Target Platform**: Existing Docker stack (`docker-compose.yml`), server-side
rendered web app.

**Project Type**: Web application (monolith) — no separate frontend/backend
split (Constitution Principle III).

**Performance Goals**: Lineage member list renders instantly up to 500
members (spec SC-003) — plain indexed pivot query, no special optimization
needed at MVP scale.

**Constraints**: Must not alter `people.team_id`/`couples.team_id` or their
existing global scope (that's spec 002's job) — this feature is additive
only.

**Scale/Scope**: New tables (`lineages`, `lineage_person`), ~4 Livewire
components, 3 routes, no changes to existing Person/Couple migrations.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-checked after Phase 1 design.*

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | PASS | This feature exists specifically to make multi-lineage membership possible without duplication (pivot table, not FK on `people`). |
| II/VIII. Living Protected / No Sensitive Data | PASS (N/A) | Lineage membership itself carries no sensitive person fields; person data rendered on the lineage member list already goes through spec 003/007 rules once those ship — until then, member list shows only name + lifespan (non-sensitive). |
| III. Laravel/Livewire Stack | PASS | All new UI is Livewire/TallStackUI, no new frontend framework. |
| IV. MySQL 8 Reference | PASS | Plain relational tables, no engine-specific SQL. |
| V. Test-First | GATE — enforced in tasks.md | Every FR gets a Pest test before being marked done. |
| VI. Reuse Recursive-Query Engine | N/A | This feature doesn't touch `app/Queries/*`. |
| VII. Teams Repositioned Not Removed | PASS | `Lineage` is explicitly independent of `team_id`; see spec 001's "Team/Lineage scope interaction" note. |
| IX. Migrations Preserve Attributes | PASS | No existing column is modified; only new tables/columns added. |
| Full-stack specs | PASS | UI & Interface Requirements section in spec.md covers routes/components/states; this plan's Project Structure section maps them to files. |

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/001-lineages/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md         # Phase 1 output
├── quickstart.md         # Phase 1 output
└── tasks.md              # Phase 2 output (/speckit-tasks — not created here)
```

### Source Code (repository root)

This is the existing Laravel monolith — no new top-level project, feature
code lands inside the current app structure:

```text
app/
├── Models/
│   ├── Lineage.php                      # NEW
│   └── LineageMembership.php            # NEW (pivot model, for Activitylog + isDeletable-style guards)
├── Http/Controllers/
│   ├── Front/LineageController.php      # NEW — public directory/show (spec 001 routes)
│   └── Back/LineageController.php       # NEW — contributor CRUD entry points, or folded into Livewire actions
├── Livewire/
│   ├── Lineages/
│   │   ├── Form.php + form.blade.php            # LineageForm (create/edit)
│   │   ├── Directory.php + directory.blade.php  # LineageDirectory (public)
│   │   └── Show.php + show.blade.php            # LineageShow (public member list)
│   └── People/PersonLineageManager.php  # NEW — embedded attach/detach widget

database/
├── migrations/
│   ├── xxxx_create_lineages_table.php           # NEW
│   └── xxxx_create_lineage_person_table.php     # NEW (pivot: lineage_id, person_id, timestamps, unique[lineage_id, person_id])
├── factories/
│   ├── LineageFactory.php               # NEW
└── seeders/ (optional demo data extension, not required for MVP)

routes/
├── web.php                              # add public `lineages` routes (front) and back routes under existing auth group

tests/Feature/
├── Lineage/
│   ├── CreateLineageTest.php
│   ├── DuplicateLineageNameWarningTest.php
│   ├── AttachPersonToLineageTest.php     # covers idempotent attach (FR-010)
│   ├── DetachPersonFromLineageTest.php
│   ├── LineageDeletionBlockedWithMembersTest.php
│   └── PublicLineageDirectoryTest.php
```

**Structure Decision**: Single Laravel monolith (existing project layout).
No `backend/`/`frontend/` split — Constitution Principle III fixes Livewire
for the public UI too, so Option 2 (web app with separate frontend) from the
template is explicitly rejected here.

## Complexity Tracking

*No Constitution Check violations — table intentionally empty.*
