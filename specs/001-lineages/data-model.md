# Phase 1 Data Model: Lineages

## Entity: Lineage

| Field | Type | Rules |
|---|---|---|
| id | bigint PK | |
| name | string(150) | required, indexed |
| slug | string(170) | required, unique, generated from `name` |
| description | text, nullable | |
| origin | string(150), nullable | free-text region/country of origin (FR-001) |
| cover_image | string, nullable | path/disk reference, same pattern as `Person.photo` |
| status | string, default `active` | reserved for future moderation states; not exercised by MVP acceptance criteria |
| created_at / updated_at | timestamps | |

**Validation rules** (Form Request `LineageRequest`):
- `name`: required, string, max:150.
- On create/update, if a lineage with the same `name` (case-insensitive,
  trimmed) already exists, do NOT block save (FR-003 is a warning, not a
  hard uniqueness constraint) — surfaced client-side by `LineageForm`'s
  debounced check; the database `slug` unique constraint is the only hard
  constraint, and slug collision is resolved by appending a numeric suffix
  (`rakoto`, `rakoto-2`, ...).

**Relationships**:
- `people()`: `belongsToMany(Person::class, 'lineage_person')->using(LineageMembership::class)->withTimestamps()`.

**Business rules**:
- `isDeletable(): bool` — mirrors `Team::isDeletable()`/`Person::isDeletable()`
  pattern: `false` if `$this->people()->exists()` (FR-007).

## Entity: LineageMembership (pivot model)

| Field | Type | Rules |
|---|---|---|
| id | bigint PK | |
| lineage_id | bigint FK → lineages.id, cascade on delete | |
| person_id | bigint FK → people.id, cascade on delete | |
| created_at / updated_at | timestamps | |

**Constraints**:
- Unique composite index on (`lineage_id`, `person_id`) — this is what makes
  attach idempotent at the database level (FR-010): `attach()`/`sync()`
  calls are wrapped to catch/ignore the unique-violation case rather than
  erroring the UI.

## Migrations

1. `xxxx_create_lineages_table.php` — new table, per Entity: Lineage above.
2. `xxxx_create_lineage_person_table.php` — new pivot table, per Entity:
   LineageMembership above, with the unique composite index.

Neither migration touches `people` or `couples` — no `down()` risk to
existing data, consistent with Constitution Principle IX (this principle
applies to *modifying* existing columns; this feature adds new tables only,
so it is trivially compliant).

## State / Lifecycle

- A `Lineage` has no formal state machine for MVP (`status` column reserved,
  not enforced). A `Person` ↔ `Lineage` membership is a simple present/absent
  edge — created on attach, removed on detach, both idempotent operations.
