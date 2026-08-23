# Quickstart: Validating Lineages

Prerequisites: local stack running (`docker compose up -d`), migrations run,
at least one seeded `Person`.

## 1. Create a lineage (User Story 1)

1. Sign in as a contributor, open `/back/lineages`, click "Créer une lignée".
2. Enter name "RAKOTO", description, save.
3. **Expected**: redirected to the lineage's page at `/lineages/rakoto` with
   zero members and the empty-state message.
4. Repeat with name "rakoto" (different case) — **expected**: a duplicate
   warning banner appears before save (FR-003); saving anyway is still
   allowed and produces a second lineage with slug `rakoto-2`.

## 2. Attach a person to two lineages (User Story 2)

1. Open an existing person's edit screen, "Lignées" section.
2. Attach "RAKOTO". **Expected**: chip appears immediately, no page reload.
3. Attach "RABE" (a second, pre-existing lineage) on the same person.
   **Expected**: both chips shown; `SELECT * FROM lineage_person WHERE
   person_id = ?` returns 2 rows; `people` table row for this person is
   unchanged (same `id`, no duplicate row).
4. Re-attach "RAKOTO" again (idempotency, FR-010). **Expected**: still
   exactly 2 rows in `lineage_person`, no error.

## 3. Browse a lineage's members (User Story 3)

1. As a signed-out visitor, open `/lineages/rakoto`.
2. **Expected**: the person from step 2 appears in the member list with
   name and lifespan.

## 4. Deletion guard (FR-007)

1. As a contributor, attempt to delete the "RAKOTO" lineage while it still
   has the member from step 2.
2. **Expected**: delete action is disabled/blocked with an explanatory
   message (per UI & Interface Requirements "Blocked action" state).
3. Detach the member, then retry delete. **Expected**: deletion succeeds.

## Automated coverage

Each manual step above corresponds to a Pest test listed in
`plan.md`'s Project Structure (`tests/Feature/Lineage/*`) — run with:

```
vendor/bin/sail artisan test --compact --filter=Lineage
```
