# Quickstart: Validating the Ancestor Explorer

## 1. Tree view (User Story 1)

1. As a signed-out visitor, open `/people/{id}/ancestors` for a person with
   2+ recorded generations of ancestors.
2. **Expected**: root node + parents shown; grandparents collapsed until
   expanded, loading only that branch.

## 2. List view with generation filter (User Story 2)

1. Switch to List view. **Expected**: same ancestor set, generation-tagged,
   filterable.

## 3. Missing parent handling (User Story 3)

1. Open the ancestor tree for a person with only a mother recorded.
2. **Expected**: father slot renders as an explicit "Parent inconnu"
   placeholder, not omitted.

## 4. Cross-team traversal

1. Using a two-team fixture (mirrors spec 002/004), open a descendant's
   ancestor tree that should reach back into another team's person.
2. **Expected**: that ancestor appears in the same traversal.

## Automated coverage

```
vendor/bin/sail artisan test --compact --filter=AncestorExplorer
```
