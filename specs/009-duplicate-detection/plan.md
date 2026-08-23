# Implementation Plan: Duplicate Detection

**Branch**: `009-duplicate-detection` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/009-duplicate-detection/spec.md`

## Summary

Extend `Person::scopeSimilarTo()` (existing, name-only per the 000 audit)
with a birth-year proximity signal to produce a weighted similarity score,
surfaced in `PersonForm` as a live, non-blocking warning before save.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Livewire 4, TallStackUI; existing
`Person::scopeSimilarTo()` extended in place (Constitution Principle VI —
"reuse over rewrite" for existing query logic where applicable, applied
here to a scope, not the recursive CTE engine which VI specifically names).

**Storage**: MySQL 8, no new table — the scoring logic runs over existing
`people` columns (`firstname`, `surname`, `birthname`, `nickname`, `dob`,
`yob`).

**Testing**: Pest unit tests for the scoring function in isolation (varying
name closeness × birth-year distance combinations) plus feature tests for
the `PersonForm` warning flow.

**Target Platform**: Existing Docker stack, monolith.

**Project Type**: Web application (monolith).

**Performance Goals**: Live-typing check (debounced 500ms per spec.md UI
section) must return within the debounce window for typical data volumes —
achievable via the existing indexed name columns.

**Constraints**: MUST search across all teams/lineages (FR-003), same
explicit `withoutGlobalScope('team')` pattern as spec 006 — this spec
depends on that pattern existing, not on spec 006's code directly (no
hard build-order dependency, just a shared pattern).

**Scale/Scope**: One scoring service class, one Livewire integration point
(`PersonForm`), no new table.

## Constitution Check

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | **This spec IS the primary enforcement mechanism at creation time** | Directly protects the constitution's first principle. |
| II/VIII. Privacy | PASS | Candidate cards show name/lifespan/lineage only — same fields already public via spec 003, no new sensitive exposure. |
| III. Laravel/Livewire Stack | PASS | `DuplicateWarningPanel` is Livewire. |
| IV. MySQL 8 | PASS | Plain query extension. |
| V. Test-First | GATE | SC-002 (low false-positive rate) requires isolated unit tests of the scoring function, not just end-to-end tests. |
| VI. Reuse Recursive-Query Engine | N/A (different mechanism — see Summary) | This spec extends `scopeSimilarTo()`, not `app/Queries/*`; Principle VI's "prefer reuse" spirit is honored by extending rather than replacing the existing scope. |
| VII. Teams Repositioned | PASS | Explicit cross-team bypass, same pattern as spec 006. |
| IX. Migrations | N/A | No migration. |
| Full-stack specs | PASS | UI section in spec.md; mapped below. |

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/009-duplicate-detection/
├── plan.md
├── research.md
├── data-model.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── Person.php                    # MODIFY scopeSimilarTo() call site OR keep as-is and layer scoring on top (see research.md)
├── Support/
│   └── PersonSimilarityScorer.php    # NEW — weighted name + birth-year scoring, pure function over two Person-like inputs
├── Livewire/People/
│   ├── PersonForm.php                # MODIFY — add live candidate check on name/dob input
│   └── DuplicateWarningPanel.php + blade   # NEW

tests/Unit/
└── PersonSimilarityScorerTest.php    # SC-002's low-false-positive-rate assertions, isolated from HTTP/Livewire

tests/Feature/DuplicateDetection/
├── WarningShownForCloseMatchTest.php        # US1
├── NoWarningForDistantBirthYearTest.php     # US1 edge, SC-002
├── LinkToExistingPersonInsteadOfCreatingTest.php  # US2
├── ConfirmDistinctPersonProceedsTest.php    # US2
└── CandidateSearchCrossesTeamsTest.php      # FR-003
```

**Structure Decision**: A dedicated `PersonSimilarityScorer` service class,
separate from the `Person::scopeSimilarTo()` query scope — the scope's job
stays "fetch plausible candidates from the database," the scorer's job is
"rank them," keeping the two concerns (retrieval vs. scoring) independently
testable (the unit test above needs no database at all).

## Complexity Tracking

*No Constitution Check violations — table intentionally empty.*
