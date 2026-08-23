# Quickstart: Validating the Global Genealogy Model

Prerequisites: two teams (Team X, Team Y) each with at least one person;
Team X's Person A and Team Y's Person B recorded as a couple with a shared
child.

## 1. Cross-team traversal (User Story 1)

1. As a Team X contributor, view Person A's descendants.
2. **Expected**: the shared child (linked to Team Y's Person B as the other
   parent) appears in the traversal.
3. As a Team Y contributor, view Person B's descendants.
4. **Expected**: the same shared child appears — confirming a single,
   non-duplicated record reachable from both sides.

## 2. Existing team-scoped editing unaffected (User Story 2)

1. Run the pre-existing Person/Couple Pest suite:
   `vendor/bin/sail artisan test --compact --filter=Person`
   `vendor/bin/sail artisan test --compact --filter=Couple`
2. **Expected**: 100% pass, unchanged from before this feature (SC-001).

## 3. Cross-team edit permission (User Story 3)

1. As a Team X contributor, attempt to edit Person B (owned by Team Y) via
   direct edit UI.
2. **Expected**: edit action denied/hidden; ownership badge shown instead
   (per spec.md UI section); "Proposer une modification" CTA shown (linking
   toward the not-yet-built spec 008 flow — CTA may be a placeholder until
   008 ships).

## 4. Developer bypass unchanged (FR-005)

1. As a developer account (`is_developer`), verify full cross-team
   visibility and edit access are unaffected by this spec.

## Automated coverage

```
vendor/bin/sail artisan test --compact --filter=Genealogy
```
