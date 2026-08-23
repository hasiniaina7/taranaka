# Implementation Plan: Descendant Explorer

**Branch**: `004-descendant-explorer` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/004-descendant-explorer/spec.md`

## Summary

Expose the existing `app/Queries/MySqlDescendantsQuery` (and its PgSQL/
SQLite siblings) through a new public route and a Livewire tree/list UI,
with progressive branch loading and a generation-limit control. Key
implementation finding from source review: the existing recursive CTE
queries (`getRecursiveQuery()`) already select from `people` with **no
`team_id` filter at all** — they are naturally cross-team already. This
significantly de-risks spec 002's dependency: FR-007 here requires no query
change, only ensuring the calling controller/Livewire layer doesn't
re-apply Eloquent's `team` global scope on top of the raw query's results.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Livewire 4, TallStackUI; existing
`App\Contracts\DescendantsQueryInterface` + `App\Queries\{MySql,PgSql,
SQLite}DescendantsQuery` (unmodified, Constitution Principle VI)

**Storage**: MySQL 8 (reference); the existing driver-selection mechanism
for `DescendantsQueryInterface` is reused as-is.

**Testing**: Pest feature tests for progressive loading, generation
limiting, and privacy filtering of descendant nodes; a dedicated test
confirming the query itself returns cross-team results (documenting the
finding above so a future refactor doesn't accidentally reintroduce a team
filter).

**Target Platform**: Existing Docker stack, monolith.

**Project Type**: Web application (monolith).

**Performance Goals**: Per spec.md SC-003 — 200+ descendants remain usable
via progressive loading; the existing query already returns the full depth
in one call (bounded by `$maxDepth`), so "progressive loading" (FR-002) is a
**UI-level** progressive reveal (client-side expand/collapse of an
already-fetched depth-bounded result set), not a series of incremental
server round-trips per branch — this is cheaper than true lazy server
loading and is possible because the existing query is already efficient up
to a bounded depth.

**Constraints**: MUST NOT modify `app/Queries/*` core recursive SQL
(Principle VI) — only the depth parameter and the calling/authorization
layer around it.

**Scale/Scope**: One new public route, one Livewire component tree
(`DescendantExplorer`, `DescendantTree`, `DescendantList`), reuse of
existing query classes.

## Constitution Check

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | PASS (N/A) | Read-only traversal. |
| II/VIII. Privacy | PASS | Each node rendered through the same `PersonPrivacy`/`PrivacyBanner` mechanism as spec 003 (FR-006). |
| III. Laravel/Livewire Stack | PASS | Tree/list UI is Livewire + a JS-light rendering approach (see research.md), no separate frontend framework. |
| IV. MySQL 8 | PASS | Reference engine; PgSQL/SQLite variants untouched. |
| V. Test-First | GATE | Cross-team traversal test is the highest-value new test (documents the research finding as an executable assertion). |
| VI. Reuse Recursive-Query Engine | **Directly honored — see Summary** | No modification to `getRecursiveQuery()`. |
| VII. Teams Repositioned | PASS | See Summary finding — no scope logic needed here, unlike spec 002. |
| IX. Migrations | N/A | No migration. |
| Full-stack specs | PASS | UI section in spec.md; mapped below. |

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/004-descendant-explorer/
├── plan.md
├── research.md
├── data-model.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/
├── Http/Controllers/Front/
│   └── DescendantsController.php   # NEW public GET /p/{person}/descendants — distinct URI from the existing authenticated `people/{person}/descendants` (see research.md's URI-collision decision)
├── Livewire/People/Descendants/
│   ├── Explorer.php + blade        # DescendantExplorer — tab switch, generation control, hosts Tree/List
│   ├── Tree.php + blade            # DescendantTree — renders nodes from DescendantsQueryInterface result, client-side expand/collapse
│   └── ListView.php + blade        # DescendantList — TallStackUI table, generation/name filters
# app/Queries/*DescendantsQuery.php — UNCHANGED (Principle VI)

tests/Feature/DescendantExplorer/
├── GuestCanViewDescendantTreeTest.php
├── ProgressiveExpandCollapseTest.php
├── GenerationLimitTest.php
├── CrossTeamDescendantsAppearInOneTraversalTest.php   # documents the research.md finding
└── LivingDescendantNodePrivacyTest.php
```

**Structure Decision**: New `Front\DescendantsController` rather than
reusing the existing authenticated `Back\PeopleController@descendants` —
same rationale as spec 003 (separate trust boundary, avoid retrofitting
privacy/guest-safety onto a controller built for authenticated,
team-scoped use).

## Complexity Tracking

*No Constitution Check violations — table intentionally empty.*
