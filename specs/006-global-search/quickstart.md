# Quickstart: Validating Global Search

## 1. Cross-team person search (User Story 1)

1. Seed several people named "Jean Rakoto" with different birth years,
   across at least two different teams.
2. As a signed-out visitor, search "Jean Rakoto" at `/search`.
3. **Expected**: all matches appear regardless of team, each disambiguated
   by birth/death year and lineage.

## 2. Lineage search (User Story 2)

1. With a "Rakoto" lineage (spec 001) existing alongside several
   Rakoto-surnamed people, search "Rakoto".
2. **Expected**: the lineage result is shown in a clearly separate,
   labeled section from person results.

## 3. Living person privacy in results (User Story 3)

1. Search a name matching a known living, non-opted-in person.
2. **Expected**: result shows name + private indicator only; `curl`-ing the
   raw response confirms no address/phone/exact dob present.

## 4. Escaping and result cap (Edge Cases)

1. Search a term containing `%` and `_`.
2. **Expected**: treated as literal characters, not SQL wildcards (no
   over-broad match).
3. Search a term matching more results than the page cap.
4. **Expected**: "Affiner votre recherche" hint shown, results paginated.

## Automated coverage

```
vendor/bin/sail artisan test --compact --filter=Search
```
