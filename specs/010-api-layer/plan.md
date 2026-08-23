# Implementation Plan: Read-Only API Layer

**Branch**: `010-api-layer` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/010-api-layer/spec.md`

## Summary

A versioned (`/api/v1/*`) read-only REST layer using Eloquent API
Resources, projecting the exact same privacy-filtered data as the public
web surfaces (specs 003–007) — no independent business logic, purely a
serialization layer over the engine those specs already built.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12 (API Resources, `routes/api.php`
already exists per the 000 audit), existing `DescendantsQueryInterface`/
`AncestorsQueryInterface`, `Person::scopeSearch()`/`Lineage::scopeSearch()`
(spec 006), `PersonPrivacy` (spec 003/007).

**Storage**: MySQL 8, no new tables — pure read projection.

**Testing**: Pest feature tests asserting field-for-field parity between
API responses and the corresponding web page's rendered data (SC-001/
SC-002 as literal, automated parity assertions, not just "looks similar").

**Target Platform**: Existing Docker stack, monolith.

**Project Type**: Web application (monolith) — API is a projection layer of
the same monolith, not a separate service (Constitution Principle III).

**Performance Goals**: SC-003 — acceptable latency at 500 items/page,
achieved by reusing the same pagination caps already required by specs
004/005/006.

**Constraints**: MUST NOT implement any privacy or traversal logic of its
own (FR-006) — every endpoint calls into the exact same
`PersonPrivacy`/query classes the web controllers use, so a future change
to those rules automatically applies to both surfaces.

**Scale/Scope**: 5 read endpoints + 1 docs page, no write endpoints (out of
scope per spec.md Assumptions).

## Constitution Check

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | PASS (N/A) | Read-only. |
| II/VIII. Privacy | PASS | Reuses `PersonPrivacy` verbatim — zero duplicated privacy logic (FR-006). |
| III. Laravel/Livewire Stack | PASS | API docs page is Blade/Livewire; the API itself is standard Laravel routing/Resources — no separate API framework introduced. |
| IV. MySQL 8 | PASS | No engine-specific code. |
| V. Test-First | GATE | SC-001/SC-002 parity tests are the core deliverable of this feature's test suite. |
| VI. Reuse Recursive-Query Engine | PASS | Descendant/ancestor endpoints call the existing query interfaces directly, no reimplementation. |
| VII. Teams Repositioned | PASS | Inherits spec 002/006's scoping decisions exactly — no new scope logic (spec.md's Team/Lineage scope interaction note). |
| IX. Migrations | N/A | No migration. |
| Full-stack specs | PASS | Developer-facing UI (docs page) specified in spec.md; mapped below. |

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/010-api-layer/
├── plan.md
├── research.md
├── data-model.md
├── contracts/
│   └── routes.md         # Phase 1 endpoint contract (this spec's interface IS the contract)
└── quickstart.md
```

### Source Code (repository root)

```text
app/
├── Http/Controllers/Api/V1/
│   ├── PersonController.php        # GET /api/v1/persons/{person}
│   ├── DescendantsController.php   # GET /api/v1/persons/{person}/descendants
│   ├── AncestorsController.php     # GET /api/v1/persons/{person}/ancestors
│   ├── LineageController.php       # GET /api/v1/lineages/{lineage}, /members
│   └── SearchController.php        # GET /api/v1/search
├── Http/Resources/
│   ├── PersonResource.php          # wraps PersonPrivacy::publicFields() (spec 003)
│   ├── LineageResource.php
│   └── SearchResultResource.php
├── Livewire/Developer/
│   └── ApiDocsPage.php + blade     # NEW, under existing developer.* route group

routes/
├── api.php   # add v1 group: Route::prefix('v1')->group(...)
├── web.php   # add `developer.api-docs` route inside existing IsDeveloper-gated group

tests/Feature/Api/V1/
├── PersonEndpointMatchesPublicProfileTest.php    # SC-001
├── DescendantsEndpointMatchesExplorerTest.php    # SC-002
├── AncestorsEndpointMatchesExplorerTest.php      # SC-002
├── SearchEndpointTest.php
├── LineageMembersEndpointTest.php
└── ListEndpointsArePaginatedTest.php             # FR-007
```

**Structure Decision**: `Api/V1` controller namespace + Eloquent API
Resources, per existing Laravel Boost convention ("For APIs, default to
using Eloquent API Resources and API versioning"). No GraphQL, no separate
API framework — plain Laravel routing, consistent with Constitution
Principle III's monolith-first mandate.

## Complexity Tracking

*No Constitution Check violations — table intentionally empty.*
