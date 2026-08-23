# Quickstart: Validating the Descendant Explorer

## 1. Tree view (User Story 1)

1. As a signed-out visitor, open `/people/{id}/descendants` for a person
   with 3+ recorded generations of descendants.
2. **Expected**: root node + first-level children shown; deeper branches
   collapsed. Click to expand — next generation reveals without a full page
   reload.

## 2. List view with generation filter (User Story 2)

1. On the same page, switch to List view.
2. **Expected**: same descendant set as the tree, each row tagged with a
   generation number; filtering by generation 2 shows only that generation.

## 3. Generation limit (User Story 3)

1. Set the generation limit control to 2 for a person with 5 known
   generations.
2. **Expected**: only 2 generations returned/shown, with an indicator that
   more exist.

## 4. Cross-team traversal (regression guard for spec 002/research finding)

1. Using the two-team fixture from spec 002's quickstart (Team X Person A ×
   Team Y Person B, shared child), open Person A's descendant tree.
2. **Expected**: the shared child appears, confirming the underlying CTE's
   already-cross-team behavior remains intact.

## Automated coverage

```
vendor/bin/sail artisan test --compact --filter=DescendantExplorer
```
