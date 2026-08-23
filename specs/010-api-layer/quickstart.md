# Quickstart: Validating the Read-Only API Layer

## 1. Person parity (User Story 1)

1. `GET /api/v1/persons/{id}` for a deceased seeded person.
2. Compare the JSON fields against the same person's public web profile
   (spec 003).
3. **Expected**: identical field set (SC-001) — run the automated parity
   test rather than eyeballing.
4. Repeat for a living, non-opted-in person. **Expected**: sensitive fields
   absent from the JSON exactly as from the web page.

## 2. Descendants/ancestors parity (User Story 2)

1. `GET /api/v1/persons/{id}/descendants?max_depth=2`.
2. Compare against the web descendant explorer (spec 004) at the same
   generation limit.
3. **Expected**: identical data set (SC-002).

## 3. Search and lineage endpoints (User Story 3)

1. `GET /api/v1/search?q=Rakoto` — **expected**: `people` and `lineages`
   both present and labeled.
2. `GET /api/v1/lineages/{id}/members` — **expected**: matches spec 001's
   lineage page member list.

## 4. Docs page

1. As a developer, open the API docs page (under the existing developer
   tooling area).
2. **Expected**: every endpoint above listed with example request/response.

## Automated coverage

```
vendor/bin/sail artisan test --compact --filter=Api
```
