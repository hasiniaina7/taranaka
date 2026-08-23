# Phase 1 Data Model: Global Search

No new tables. Adds one model scope; reuses one existing scope.

## `Lineage::scopeSearch()` (new)

```php
#[Scope]
public function scopeSearch(Builder $query, string $searchString): void
{
    // mirrors Person::scopeSearch()'s guard + escape pattern, single-field:
    // WHERE name LIKE %escaped(term)%
}
```

## `Person::scopeSearch()` (existing, unmodified — see 000 audit)

Reused as-is; called with the `team` global scope explicitly removed
(`Person::withoutGlobalScope('team')->search($q)`) rather than any change to
the scope method itself.

## Derived: Search Result View Model (not persisted)

```text
SearchResults {
    people: Paginator<PersonPublicProjection>   // spec 003's projection, privacy-filtered
    lineages: Paginator<LineageSummary>          // id, name, slug, member_count
}
```

`PersonPublicProjection` reuses spec 003's `PersonPrivacy::publicFields()`
projection rather than defining a second one — avoids two implementations
of the same privacy rule (spec 007's centralization goal, honored ahead of
007 shipping since 003 already established the seam).

## State / Lifecycle

None — stateless query-per-request.
