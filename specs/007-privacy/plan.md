# Implementation Plan: Privacy Rules for Living People

**Branch**: `007-privacy` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/007-privacy/spec.md`

## Summary

Formalize the `PersonPrivacy` helper introduced provisionally in spec 003
into the single authoritative privacy rule for the whole platform: add an
opt-in flag on `Person`, wire automatic protection lift/reapply on death
date changes, and update `PersonPrivacy::isPubliclyVisible()` (the seam
already established in spec 003's data-model.md) to consult it — without
changing any call site in specs 003/004/005/006.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Livewire 4; existing
`Spatie\Activitylog` (reused for opt-in/opt-out auditability, FR-007's
traceability requirement, consistent with the pattern already on `Person`).

**Storage**: MySQL 8 — one new nullable boolean-equivalent column on
`people` (`is_publicly_visible`, default `false`), added via a migration
that, per Constitution Principle IX, only *adds* a column (no existing
column attributes at risk).

**Testing**: Pest tests for opt-in/opt-out, automatic lift on death-date
recorded, automatic reapply on death-date removed, and — critically — a
shared test asserting specs 003/004/005/006 all consult the same helper
(SC-001's "single shared test suite rather than four separate ad hoc
checks").

**Target Platform**: Existing Docker stack, monolith.

**Project Type**: Web application (monolith).

**Performance Goals**: Opt-in/opt-out and death-date changes take effect on
next page load (SC-003) — no caching layer to invalidate at MVP scale.

**Constraints**: MUST NOT change the public signature/call sites of
`PersonPrivacy::isPubliclyVisible()` established in spec 003 — only its
internal implementation.

**Scale/Scope**: One migration, one model change (`Person`), one policy/
authorization check (who may toggle opt-in), no new Livewire components
beyond `PrivacyToggle` already scoped in spec.md's UI section.

## Constitution Check

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | PASS (N/A) | No identity change. |
| II/VIII. Privacy | **This spec IS the formal implementation of Principles II/VIII** | Directly executes the constitution's core safety requirement. |
| III. Laravel/Livewire Stack | PASS | `PrivacyToggle` is Livewire. |
| IV. MySQL 8 | PASS | Single boolean column, no engine-specific behavior. |
| V. Test-First | GATE | SC-001 (0% leakage) is the highest-stakes test in the entire roadmap. |
| VI. Reuse Recursive-Query Engine | N/A | Not a traversal feature. |
| VII. Teams Repositioned | PASS | Privacy is orthogonal to team ownership; spec.md's "Team/Lineage scope interaction" section states this explicitly (Development Workflow mandates the section be present on every spec touching `Person`, even when the conclusion is "no interaction"). |
| IX. Migrations Preserve Attributes | PASS | Additive-only migration; no existing column touched. |
| Full-stack specs | PASS | UI section in spec.md; mapped below. |

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/007-privacy/
├── plan.md
├── research.md
├── data-model.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/
├── Support/
│   └── PersonPrivacy.php    # MODIFY — isPubliclyVisible() now also checks $person->is_publicly_visible
├── Models/
│   └── Person.php           # ADD $fillable entry + cast for is_publicly_visible; ADD model event or observer auto-toggling on dod/yod change (FR-006/FR-007)
├── Policies/
│   └── PersonPolicy.php     # EXTEND (from spec 002) with a togglePrivacy ability, restricted to edit-rights holders (FR-004)
├── Livewire/People/
│   └── PrivacyToggle.php + blade   # NEW

database/migrations/
├── xxxx_add_is_publicly_visible_to_people_table.php   # NEW, additive-only

tests/Feature/Privacy/
├── LivingPersonDefaultProtectedAcrossAllSurfacesTest.php   # runs against 003/004/005/006 fixtures — the "single shared test suite" from SC-001
├── OptInMakesPersonPubliclyVisibleTest.php
├── OptOutReappliesProtectionTest.php
├── DeathDateAutoLiftsProtectionTest.php
├── DeathDateRemovalReappliesProtectionTest.php
└── UnauthorizedToggleDeniedTest.php
```

**Structure Decision**: No new controller — this spec modifies the shared
`PersonPrivacy` helper and `Person` model in place, and adds one Livewire
toggle component, consistent with spec 003's data-model.md explicitly
reserving this exact seam.

## Complexity Tracking

*No Constitution Check violations — table intentionally empty.*
