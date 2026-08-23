# Implementation Plan: Global Search

**Branch**: `006-global-search` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/006-global-search/spec.md`

## Summary

Extend the existing `Person::scopeSearch()` (already multi-word, LIKE-based,
properly escaped per the 000 audit) into a public search endpoint that
explicitly bypasses the `team` global scope, adds a parallel `Lineage` name
search (spec 001), and applies the spec 003/007 privacy filter to each
person result.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Livewire 4, TallStackUI; existing
`Person::scopeSearch()` extended, new `Lineage::scopeSearch()` (spec 001
entity, simple name LIKE).

**Storage**: MySQL 8, LIKE-based search (Constitution Principle IV — no
Meilisearch/dedicated search engine for MVP).

**Testing**: Pest feature tests for disambiguation, privacy filtering,
pagination cap, and special-character escaping (reusing the existing
escaping test pattern already implied by `scopeSearch`'s `$escapeLike`
closure).

**Target Platform**: Existing Docker stack, monolith.

**Project Type**: Web application (monolith).

**Performance Goals**: SC-001 — sub-1-second results up to 50,000 people;
achievable with the existing indexed columns (`firstname`, `surname`,
`birthname`, `nickname` are already indexed per the 000 audit's migration
review) — no new index required for MVP scale.

**Constraints**: MUST explicitly bypass, not accidentally rely on absence
of, the `team` global scope (spec 002 Edge Cases warning) — this must be an
intentional `withoutGlobalScope('team')` call, documented as such.

**Scale/Scope**: One public route/component, one scope addition on
`Lineage`, an explicit scope-bypass on `Person`/`Couple` search queries.

## Constitution Check

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | PASS (N/A) | Read-only. |
| II/VIII. Privacy | PASS | Person results reuse `PersonPrivacy` (spec 003) — no duplicated filter logic (FR-005). |
| III. Laravel/Livewire Stack | PASS | `SearchBar`/`SearchResults` are Livewire. |
| IV. MySQL 8 | **Directly honored** | LIKE-based search only, no external search engine, per spec.md Assumptions and constitution. |
| V. Test-First | GATE | Escaping and privacy tests are highest priority (both are security-adjacent). |
| VI. Reuse Recursive-Query Engine | N/A | Not a traversal feature. |
| VII. Teams Repositioned | PASS | Explicit, intentional scope bypass — see Constraints above and spec.md's Team/Lineage scope interaction note. |
| IX. Migrations | N/A | No migration (reuses existing indexed columns). |
| Full-stack specs | PASS | UI section in spec.md; mapped below. |

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/006-global-search/
├── plan.md
├── research.md
├── data-model.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   ├── Person.php      # scopeSearch() already exists — reused, no signature change
│   └── Lineage.php     # ADD scopeSearch() (spec 001 entity) — simple name LIKE, same escaping helper pattern
├── Http/Controllers/Front/
│   └── SearchController.php   # NEW GET /search — explicit ->withoutGlobalScope('team') on Person/Couple queries
├── Livewire/
│   ├── SearchBar.php + blade       # NEW — mounted in public layout header
│   └── SearchResults.php + blade   # NEW — grouped Person/Lineage results, paginated

routes/
├── web.php   # add `Route::get('search', [SearchController::class, 'show'])->name('public.search')` OUTSIDE auth group (distinct from existing authenticated `people.search`)

tests/Feature/Search/
├── SearchAcrossTeamsTest.php
├── SearchDisambiguatesSameNameResultsTest.php
├── SearchLineageResultsTest.php
├── SearchWithholdsLivingPersonSensitiveFieldsTest.php
├── SearchEscapesSpecialCharactersTest.php
└── SearchCapsResultVolumeTest.php
```

**Structure Decision**: New `Front\SearchController`/public route, distinct
from the existing authenticated `people.search` route (`PeopleController@search`,
team-scoped) — same separate-trust-boundary rationale as specs 003/004/005.

## Complexity Tracking

*No Constitution Check violations — table intentionally empty.*
