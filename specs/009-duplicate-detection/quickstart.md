# Quickstart: Validating Duplicate Detection

## 1. Warning on close match (User Story 1)

1. Seed "Jean Rakoto, born 1954" in Team X.
2. As a contributor in Team Y, start creating "Jean Rakoto" with birth year
   1954.
3. **Expected**: `DuplicateWarningPanel` shows the existing match with a
   high similarity score, before save.

## 2. Resolve as same person (User Story 2)

1. From the warning, choose "C'est la même personne → lier."
2. **Expected**: no new person created; redirected toward attaching/using
   the existing person.

## 3. Resolve as different person (User Story 2)

1. Trigger a warning, choose "Ce sont des personnes différentes →
   continuer."
2. **Expected**: creation proceeds, new person created, decision logged
   (Activitylog entry referencing the dismissed candidate).

## 4. Score reflects birth-year proximity (User Story 3)

1. Seed a second "Jean Rakoto" born 1910 (40 years apart from the 1954
   one).
2. Start creating a third "Jean Rakoto" born 1954.
3. **Expected**: the 1954 match scores high (warning shown), the 1910 match
   scores low (no warning, or shown as low-confidence only).

## Automated coverage

```
vendor/bin/sail artisan test --compact --filter=PersonSimilarityScorer
vendor/bin/sail artisan test --compact --filter=DuplicateDetection
```
