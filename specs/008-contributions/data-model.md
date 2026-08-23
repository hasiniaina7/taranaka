# Phase 1 Data Model: Contributions and Moderation

## Entity: Contribution

| Field | Type | Rules |
|---|---|---|
| id | bigint PK | |
| author_id | bigint FK → users.id | required; retained even if author account is later deactivated (Edge Cases) — no cascade delete, `nullOnDelete()` not used, FK uses `restrictOnDelete()` or the author relation is kept nullable-safe via soft-deletes on users if applicable |
| target_type | string | `person`, `couple`, `person_new`, `relationship_new` (FR-001/FR-008) |
| target_id | bigint, nullable | null for `person_new` (FR-008's "queued for review without creating the person record yet") |
| field | string, nullable | e.g. `surname`, null for whole-new-entity proposals |
| old_value | text, nullable | |
| new_value | text | required |
| justification | text, nullable | |
| status | string, default `pending` | `pending` \| `accepted` \| `rejected` |
| reviewer_id | bigint FK → users.id, nullable | set on decision |
| reviewed_at | timestamp, nullable | |
| rejection_reason | text, nullable | required when status = `rejected` (FR-006), enforced at the Form Request level |
| created_at / updated_at | timestamps | |

**Validation rules** (`ContributionRequest` for propose, `ContributionDecisionRequest` for review):
- `new_value`: required.
- `rejection_reason`: required if the decision action is reject.
- Author cannot review their own contribution (self-review guard).

**Relationships**:
- `author(): BelongsTo<User>`
- `reviewer(): BelongsTo<User>` (nullable)
- `target(): MorphTo`-style resolution by `target_type`/`target_id` — implemented as an explicit accessor (not Eloquent's built-in polymorphic relation, since `target_type` values include non-model states like `person_new`) rather than a literal `morphTo()`. For `target_type` values `person`/`couple`, the accessor MUST resolve via `Person::withoutGlobalScope('team')->find($target_id)` / `Couple::withoutGlobalScope('team')->find($target_id)` — the target is frequently outside the current viewer's team (that's this spec's reason to exist per FR-004), and the default `team` global scope would otherwise silently return null for exactly that case.

**Business rules**:
- Accepting (`status → accepted`) triggers applying `new_value` to
  `target_type`/`target_id`/`field` via the normal Eloquent update path
  (research.md decision) — for `person_new`/`relationship_new`, accepting
  creates the new `Person`/`Couple` record and back-fills `target_id`.
- Rejecting (`status → rejected`) requires `rejection_reason`, applies no
  change to `target`.
- A `target` that no longer exists/no longer has the proposed field at
  review time is flagged (Edge Cases) via a computed `isStillApplicable(): bool`
  check surfaced in `ContributionReview`, not a stored column (avoids a
  background job to keep it in sync — computed at render time is sufficient
  at MVP scale).

## Migration

`xxxx_create_contributions_table.php` — new table only, no existing table
modified (Constitution Principle IX trivially satisfied).

## State / Lifecycle

```text
pending → accepted (moderator, applies new_value to target)
pending → rejected (moderator, requires rejection_reason)
```
No further transitions — a decided contribution is immutable (FR-007's
traceability requirement).
