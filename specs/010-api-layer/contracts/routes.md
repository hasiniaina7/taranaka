# API Contract: v1 Read-Only Endpoints

Base path: `/api/v1`. All endpoints unauthenticated, read-only, GET only.

| Method | Route | Maps to (spec) | Response |
|---|---|---|---|
| GET | `/persons/{person}` | 003 | `PersonResource` |
| GET | `/persons/{person}/descendants?max_depth=N` | 004 | Paginated array of `PersonResource` + `degree` |
| GET | `/persons/{person}/ancestors?max_depth=N` | 005 | Paginated array of `PersonResource` + `degree` |
| GET | `/lineages/{lineage}` | 001 | `LineageResource` |
| GET | `/lineages/{lineage}/members?page=N` | 001 | Paginated array of `PersonResource` |
| GET | `/search?q=...&page=N` | 006 | `SearchResultResource` |

## Common behaviors (all endpoints)

- 404 for a nonexistent/soft-deleted target, matching spec 003 FR-007 (no
  enumeration hint).
- `max_depth`/`page` default to the same values as their web-page
  counterparts (specs 004/005/006) when omitted.
- Every list response includes standard Laravel pagination `meta`
  (`current_page`, `last_page`, `per_page`, `total`).
- Privacy filtering (spec 007) is always applied — there is no parameter to
  bypass it.

## Versioning policy (FR-008)

Breaking changes to any response shape above require a new `v2` prefix;
`v1` is not modified in a breaking way once shipped, per Constitution
Governance's amendment discipline applied to this interface.
