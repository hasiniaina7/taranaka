# Implementation Plan: Contributions and Moderation

**Branch**: `008-contributions` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/008-contributions/spec.md`

## Summary

New `Contribution` entity capturing propose→review→accept/reject, distinct
from (but complementary to) the existing `Spatie\Activitylog` technical
change history already on `Person`/`Couple`. This is the mechanism spec 002
relies on for cross-team edits (FR-004 there) and spec 002's ownership
badge CTA links directly into this feature's propose route.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Livewire 4, TallStackUI; existing
`Spatie\Activitylog` (reused for the *accepted* change's technical log
entry — applying an accepted contribution still goes through normal
Eloquent save, so it's logged exactly like a direct edit today).

**Storage**: MySQL 8. New `contributions` table.

**Testing**: Pest feature tests for the full propose→accept and
propose→reject lifecycles, plus authorization tests (moderator-only
accept/reject, FR-005/FR-006) and the direct-edit-denial redirect (FR-003,
depends on spec 002's `PersonPolicy`).

**Target Platform**: Existing Docker stack, monolith.

**Project Type**: Web application (monolith).

**Performance Goals**: Moderator queue loads instantly up to a few hundred
pending items (SC-002's 1-minute review target is a UX goal, not a
performance one at MVP scale).

**Constraints**: Depends on spec 002's `PersonPolicy` for FR-003 (route
non-owners to propose instead of edit) — this spec cannot be fully wired
without 002 landing first, though its own CRUD (propose/review/decide) is
independently buildable and testable against a stubbed authorization check.

**Scale/Scope**: One new table, ~6 Livewire components, moderator role
reuse from existing role system (not redefined here per spec.md
Assumptions).

## Constitution Check

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | PASS | New-person proposals (US3) go through the same duplicate-detection flow as direct creation once spec 009 lands — noted as a sequencing dependency, not a violation. |
| II/VIII. Privacy | PASS (N/A) | Proposals target existing fields; no new public exposure surface. |
| III. Laravel/Livewire Stack | PASS | All new UI is Livewire. |
| IV. MySQL 8 | PASS | Plain table, no engine-specific SQL. |
| V. Test-First | GATE | FR-002 ("no live-data change before acceptance") is the single most safety-critical assertion in this feature. |
| VI. Reuse Recursive-Query Engine | N/A | Not a traversal feature. |
| VII. Teams Repositioned | **This spec is spec 002's designated cross-team mechanism** | See spec.md's Team/Lineage scope interaction note; this plan adds no independent scope logic, it consumes spec 002's `PersonPolicy`. |
| IX. Migrations | PASS | New table only. |
| Full-stack specs | PASS | UI section in spec.md; mapped below. |

**Sequencing dependency (not a violation)**: FR-003 requires spec 002's
`PersonPolicy`; FR-008 (new-person proposals) benefits from spec 009's
duplicate check but doesn't strictly require it for MVP (a proposal could
still create a duplicate that a human moderator catches manually until 009
ships).

## Project Structure

### Documentation (this feature)

```text
specs/008-contributions/
├── plan.md
├── research.md
├── data-model.md
└── quickstart.md
```

### Source Code (repository root)

```text
app/
├── Models/
│   └── Contribution.php             # NEW
├── Policies/
│   └── ContributionPolicy.php       # NEW — moderator-only accept/reject (FR-005/FR-006)
├── Livewire/Contributions/
│   ├── ProposeChange.php + blade         # inline field-correction proposal (US1)
│   ├── ProposeNewPerson.php + blade      # US3
│   ├── ProposeRelationship.php + blade   # US3
│   ├── MyContributions.php + blade       # authenticated user's own list
│   ├── ModerationQueue.php + blade       # moderator queue (US2)
│   └── ContributionReview.php + blade    # accept/reject with diff view

routes/
├── web.php   # add /people/{person}/propose, /contributions, /moderation/contributions[/{contribution}]

tests/Feature/Contributions/
├── ProposeCorrectionDoesNotChangeLiveDataTest.php   # FR-002
├── DirectEditDeniedRoutesToProposalTest.php          # FR-003, depends on spec 002
├── ModeratorAcceptsProposalAppliesChangeTest.php     # FR-005
├── ModeratorRejectsProposalWithReasonTest.php        # FR-006
├── ProposeNewPersonAndRelationshipTest.php           # FR-008
├── FullTraceabilityRetainedRegardlessOfStatusTest.php # FR-007
└── AuthorNotifiedOnDecisionTest.php                  # FR-009
```

**Structure Decision**: Single new `Contribution` model/table rather than
overloading `Spatie\Activitylog`'s `activity_log` table — the two serve
different purposes (pre-application review queue vs. post-application
technical history) per spec.md's Assumptions, and conflating them would
make both harder to query correctly.

## Complexity Tracking

*No Constitution Check violations — table intentionally empty. The
sequencing dependency on spec 002 is a build-order note, not a
constitutional violation.*
