<!--
Sync Impact Report
Version change: 1.0.0 → 1.1.0
Modified principles: n/a
Added sections: Development Workflow gained a "Specs are full-stack" requirement
  (every feature spec must include a UI & Interface Requirements section: routes,
  Livewire components, key screen states — not backend/API behavior only).
Removed sections: n/a
Deferred/TODO placeholders: none.
Rationale: user explicitly requested that every spec (001-010) describe the UI
  side, not only backend/business behavior, since the stack is already fixed
  (Principle III) and there is no longer a "which framework" ambiguity that
  would justify staying implementation-agnostic on the UI.
-->

<!--
Sync Impact Report (previous)
Version change: none → 1.0.0 (initial ratification)
Modified principles: n/a (first version)
Added sections: Core Principles (I–IX), Technology Constraints, Development Workflow, Governance
Removed sections: n/a
Deferred/TODO placeholders: none — all values supplied from specs/000-project-foundation/spec.md,
  validated with the project owner during the 000-project-foundation audit.
-->
# Taranaka Constitution

## Core Principles

### I. One Person, One Entity
A person MUST exist as exactly one row in the database, regardless of how many
lineages (`Lineage`) they are connected to through birth, marriage, or adoption.
No feature may duplicate a `Person` record to represent membership in multiple
families.
Rationale: the product's entire value proposition is a connected genealogical
graph, not isolated per-family trees. Duplication breaks that graph and creates
irreconcilable data drift.

### II. Living Persons Are Private By Default
A person without a known death date (`dod`/`yod`) MUST NOT be exposed through
any public-facing route, API endpoint, or search result unless an explicit,
auditable opt-in exists for that person. Sensitive fields (address, phone,
exact birth date of the living) follow the same default.
Rationale: this is a public collaborative platform; the deceased-vs-living
distinction is the platform's core safety boundary and must never depend on a
developer remembering to add a filter.

### III. Laravel/Livewire Is the MVP Stack, Public UI Included
The MVP — including the public-facing UI — is built on Laravel 12 + Livewire 4
+ TallStackUI. A separate Next.js/React frontend is explicitly deferred to a
post-MVP specification and MUST NOT be started before the genealogical engine
(lineages, descendant/ancestor exploration, duplicate detection) is validated
end-to-end on the current stack.
Rationale: validated with the project owner — building two frontends in
parallel before a single use case works end-to-end is the highest-risk path to
scope drift.

### IV. MySQL 8 Is the Reference Database
MySQL 8 is the database the MVP is developed and tested against. The existing
multi-engine CTE support in `app/Queries/{MySql,PgSql,SQLite}*Query.php` is
preserved but is NOT a blocking requirement for MVP feature completion.
Rationale: avoids a database migration that has no concrete driver in the MVP
scope while not discarding portability work already paid for.

### V. Test-First for Business Logic (NON-NEGOTIABLE)
Every new or changed unit of business logic (model method, scope, service,
Livewire action, controller action) MUST have a corresponding Pest test
(feature or unit) before it is considered done. `vendor/bin/sail artisan test
--compact` with a targeted filter MUST pass before a task is marked complete.
Rationale: this is a collaborative genealogical database — silent regressions
corrupt family history data that cannot be trivially reconstructed.

### VI. Reuse the Existing Recursive-Query Engine
The recursive CTE ancestor/descendant queries in `app/Queries/*` are not
rewritten. They MUST be adapted (parameters, scoping) to the `Lineage` model
introduced in spec 001/002, not replaced or reimplemented from scratch.
Rationale: this code is already correct, already portable across three
database engines, and re-deriving it is pure waste.

### VII. Teams Are Repositioned, Not Removed
Jetstream Teams and the `team_id` columns on `people`/`couples` are not
deleted. Their role changes progressively from "single tree container" (one
person → exactly one team) to "contributor/permission group" as `Lineage`
becomes the entity that models genealogical grouping. Any spec touching
`Person`/`Couple` MUST state explicitly how it interacts with the existing
`team` global scope on those models.
Rationale: this is the structural blocker identified in the 000 audit — it is
the first deliberate architectural decision of the project, not an
afterthought discovered mid-implementation.

### VIII. No Sensitive Data Without a Privacy Rule
No field classified as sensitive (postal address, phone, exact address of a
living person, recent-death details) is rendered on any public route without
passing through the privacy rule defined in the `007-privacy` specification.
This applies even before spec 007 ships — until it exists, public routes MUST
NOT render these fields at all.
Rationale: makes Principle II enforceable in practice, not just in intent, for
every spec built before privacy rules formally land.

### IX. Migrations Preserve Existing Column Attributes
Any migration that modifies an existing column MUST restate every attribute
already defined on that column (nullable, default, index, foreign key), per
Laravel's column-modification semantics already in force in this codebase.
Rationale: Laravel silently drops unrestated attributes on column change —
already a known footgun flagged in project conventions (CLAUDE.md).

## Technology Constraints

- Backend: PHP 8.4, Laravel 12, Livewire 4, TallStackUI, Filament (admin),
  Laravel Fortify, Laravel Sanctum, Pest 4 / PHPUnit 12, Larastan 3, Pint 1.
- Database: MySQL 8 (reference), PgSQL/SQLite query variants preserved but
  non-blocking.
- Infrastructure: Docker (project's own `docker-compose.yml`; Sail present as
  a dependency but the custom compose stack is what's actually run).
- Dependencies are not added or upgraded without explicit approval from the
  project owner (existing CLAUDE.md convention, restated here as binding).
- Do not use `env()` outside config files; use `config()` accessors, per
  existing Laravel Boost guidelines already governing this codebase.

## Development Workflow

- Every feature is scoped as a numbered spec under `specs/NNN-feature-name/`
  before implementation begins, following the sequence agreed in
  `specs/000-project-foundation/spec.md` (001-lineages through
  010-api-layer).
- Every spec that touches `Person`, `Couple`, or the `team` global scope MUST
  include an explicit "Team scope interaction" section (Principle VII).
- Code style: `vendor/bin/sail bin pint --dirty --format agent` MUST be run
  before any PHP change is considered finished.
- New Eloquent models, migrations, controllers, and tests are generated via
  `vendor/bin/sail artisan make:*` commands, not hand-rolled, per existing
  project convention.
- Form Request classes are used for all validation; no inline controller
  validation.
- Specs are full-stack: every feature spec (numbered 001 and above) MUST
  include a "UI & Interface Requirements" section naming the concrete
  routes, Livewire components, and key screen states (loading, empty, error,
  privacy-limited) involved — a spec describing backend/data behavior alone
  is incomplete, since Principle III already fixes the stack and leaves no
  ambiguity to preserve by omitting UI detail.

## Governance

This constitution supersedes ad hoc conventions where the two conflict. Every
spec, plan, and task produced by Spec Kit commands (`/speckit-specify`,
`/speckit-plan`, `/speckit-tasks`) MUST be checked against these principles
before implementation starts; a spec that requires violating a principle MUST
either be revised or amend this document first, with the conflict stated
explicitly in the spec's own text.

Amendments follow semantic versioning:
- MAJOR: a principle is removed or redefined in a backward-incompatible way
  (e.g. reversing Principle I or III).
- MINOR: a new principle or materially expanded section is added.
- PATCH: wording, clarification, or non-semantic correction.

Each amendment updates `Last Amended` below and prepends a Sync Impact Report
to this file describing what changed and why. Compliance is reviewed at the
start of every new spec (000-series audit already performed; subsequent specs
inherit this constitution without re-auditing it).

**Version**: 1.1.0 | **Ratified**: 2026-08-23 | **Last Amended**: 2026-08-23
