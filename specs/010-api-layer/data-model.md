# Phase 1 Data Model: Read-Only API Layer

No new tables. Serialization-only projections over existing entities.

## `PersonResource`

Wraps `PersonPrivacy::publicFields(Person $person)` (spec 003/007) — same
field set as the public web profile, no additions, no omissions:

```json
{
  "id": 123,
  "name": "Jean Rakoto",
  "lifespan": "1928–2003",
  "photo_url": "...",
  "summary": "...",
  "lineages": [{"id": 1, "name": "Rakoto", "slug": "rakoto"}],
  "parents": [{"id": 45, "name": "Paul Rakoto"}],
  "partners": [{"id": 67, "name": "Marie Rabe"}],
  "children": [{"id": 89, "name": "Alice Rakoto"}],
  "is_private": false
}
```

For a living, non-opted-in person, `is_private: true` and
address/phone/exact-dob-derived fields are absent from the payload
entirely (not null — absent), matching FR-006.

## `LineageResource`

```json
{"id": 1, "name": "Rakoto", "slug": "rakoto", "description": "...", "member_count": 42}
```

## `SearchResultResource`

```json
{
  "people": {"data": [PersonResource, ...], "meta": {"current_page": 1, "last_page": 3}},
  "lineages": {"data": [LineageResource, ...], "meta": {...}}
}
```

## Descendants/Ancestors endpoints

Return the same flat, `degree`-tagged collection shape as
`DescendantsQueryInterface`/`AncestorsQueryInterface` (spec 004/005), each
row passed through `PersonResource` for privacy filtering, paginated/capped
per FR-007.

## State / Lifecycle

None — stateless request/response projections.
