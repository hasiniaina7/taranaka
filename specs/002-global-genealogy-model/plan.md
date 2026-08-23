# Implementation Plan: Global Genealogy Model

**Branch**: `002-global-genealogy-model` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/002-global-genealogy-model/spec.md`

## Summary

Refine the existing `team` global scope on `Person`/`Couple` so genealogical
traversal (couples, parent/child) crosses team boundaries wherever a real
relationship connects two teams' data, while direct edit permission stays
strictly tied to a record's own `team_id`. No schema change — this is a
scope/query-logic change plus new UI affordances (ownership badge,
propose-a-change CTA) surfaced on existing and upcoming screens.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12 (Eloquent global scopes, query
builder), Livewire 4 (existing `PersonForm`/show components)

**Storage**: MySQL 8. No migration required — `team_id` columns already
exist on `people`/`couples`.

**Testing**: Pest feature tests exercising the *existing* team-scoped CRUD
suite (must remain green, SC-001) plus new tests for cross-team traversal
and edit-permission denial.

**Target Platform**: Existing Docker stack, monolith.

**Project Type**: Web application (monolith).

**Performance Goals**: Traversal crossing team boundaries must not introduce
N+1 queries relative to today's single-team traversal (same query shape,
different `WHERE` predicate).

**Constraints**: Zero observable regression for contributors who never leave
their own team (User Story 2) — this is the highest-risk spec in the
roadmap precisely because it touches the global scope every other
Person/Couple query depends on.

**Scale/Scope**: Modify `Person::booted()` and `Couple::booted()` global
scope logic; add an ownership-check helper reused by controllers/Livewire
components; no new tables.

## Constitution Check

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | PASS | Unaffected — this spec changes visibility/traversal, not identity. |
| II/VIII. Privacy | PASS (N/A) | This spec governs team/edit visibility, a separate axis from public/private (spec 007); no conflict. |
| III. Laravel/Livewire Stack | PASS | Ownership badge/CTA are Livewire/Blade additions only. |
| IV. MySQL 8 | PASS | No engine-specific change. |
| V. Test-First | GATE | SC-001 (100% existing tests green) is itself a testable gate — CI must run the full existing Person/Couple suite before this is considered done, not just new tests. |
| VI. Reuse Recursive-Query Engine | PASS | `app/Queries/*` CTEs are parameterized, not rewritten (spec 004/005 depend on this). |
| VII. Teams Repositioned Not Removed | **This spec IS Principle VII's implementation** | Directly executes the constitution's mandate. |
| IX. Migrations | N/A | No migration in this spec. |
| Full-stack specs | PASS | Ownership badge/CTA specified in spec.md UI section. |

**Risk flag (not a violation, a sequencing note)**: This spec's FR-003
("reachable through a relationship from something already visible") is the
single highest-risk requirement in the whole roadmap — it changes a global
scope every other query implicitly trusts. Phase 0 research below dedicates
extra attention to this.

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/002-global-genealogy-model/
├── plan.md
├── research.md
├── data-model.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── Person.php     # MODIFY: booted() global scope logic (FR-003)
│   └── Couple.php     # MODIFY: booted() global scope logic (FR-003)
├── Policies/
│   └── PersonPolicy.php   # NEW (or extend existing authorization) — FR-004 ownership rule, single source of truth reused by controllers, Livewire, and the ownership badge
├── Livewire/People/
│   └── Show.php        # MODIFY: render ownership badge + propose-a-change CTA when $person->team_id !== current team

tests/Feature/Genealogy/
├── CrossTeamCoupleTraversalTest.php     # US1
├── ExistingTeamScopedCrudUnaffectedTest.php  # US2 — re-runs/extends existing suite
├── CrossTeamEditDeniedTest.php          # US3
└── DeveloperBypassUnchangedTest.php     # FR-005
```

**Structure Decision**: Same monolith; this spec is concentrated in
`Person`/`Couple` model scope logic plus a single new `PersonPolicy` that
becomes the one place FR-004's ownership rule is evaluated — deliberately
avoiding scattering the same `team_id === current team` check across
multiple controllers/components (each of which would otherwise need to
reimplement it, risking drift).

## Complexity Tracking

*No Constitution Check violations — table intentionally empty. The risk flag
above is a sequencing/testing concern, not a constitutional violation.*
