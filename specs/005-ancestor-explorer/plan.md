# Implementation Plan: Ancestor Explorer

**Branch**: `005-ancestor-explorer` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/005-ancestor-explorer/spec.md`

## Summary

Mirrors spec 004 exactly, traversing upward via
`App\Queries\{MySql,PgSql,SQLite}AncestorsQuery` instead of the descendants
variant. Same research findings apply: the ancestor CTE (verified by the
same source-review pattern as spec 004's `MySqlDescendantsQuery` — see
research.md) has no `team_id` filter, so it is already cross-team, and the
same UI-level progressive-reveal approach is used. The one behaviorally new
requirement versus spec 004 is FR-006: an explicit "unknown parent" slot
when only one parent is recorded.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Livewire 4, TallStackUI; existing
`App\Contracts\AncestorsQueryInterface` + driver implementations
(unmodified, Constitution Principle VI)

**Storage**: MySQL 8 (reference).

**Testing**: Pest feature tests mirroring spec 004's suite, plus a
dedicated missing-parent-slot test (FR-006, unique to this spec).

**Target Platform**: Existing Docker stack, monolith.

**Project Type**: Web application (monolith).

**Performance Goals**: Same as spec 004 — 3 levels expand smoothly.

**Constraints**: MUST NOT modify `app/Queries/*AncestorsQuery.php` core SQL
(Principle VI).

**Scale/Scope**: One new public route, one Livewire component tree
(`AncestorExplorer`, `AncestorTree`, `AncestorList`), reusing spec 004's
`Explorer`/tree shell pattern for interface consistency (per spec.md
Assumptions: "expected to share the bulk of their implementation").

## Constitution Check

| Principle | Status | Note |
|---|---|---|
| I–V, VIII, IX | PASS | Identical rationale to spec 004's Constitution Check. |
| VI. Reuse Recursive-Query Engine | PASS | `*AncestorsQuery.php` untouched — verify via the same source-review method used for descendants. |
| VII. Teams Repositioned | PASS | Same finding as spec 004: raw CTE bypasses Eloquent scope already. |
| Full-stack specs | PASS | UI section in spec.md; mapped below. |

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/005-ancestor-explorer/
├── plan.md
├── research.md
├── data-model.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/
├── Http/Controllers/Front/
│   └── AncestorsController.php     # NEW public GET /people/{person}/ancestors
├── Livewire/People/Ancestors/
│   ├── Explorer.php + blade        # AncestorExplorer — same shell pattern as DescendantExplorer
│   ├── Tree.php + blade            # AncestorTree — includes "unknown parent" placeholder rendering (FR-006)
│   └── ListView.php + blade        # AncestorList
# app/Queries/*AncestorsQuery.php — UNCHANGED (Principle VI)

tests/Feature/AncestorExplorer/
├── GuestCanViewAncestorTreeTest.php
├── ProgressiveExpandCollapseTest.php
├── GenerationLimitTest.php
├── UnknownParentSlotTest.php               # unique to this spec (FR-006)
├── CrossTeamAncestorsAppearInOneTraversalTest.php
└── LivingAncestorNodePrivacyTest.php
```

**Structure Decision**: Mirrors spec 004's `Front\DescendantsController` /
`Livewire/People/Descendants/*` structure under an `Ancestors` namespace,
for the same separate-trust-boundary rationale.

## Complexity Tracking

*No Constitution Check violations — table intentionally empty.*
