# Phase 1 Data Model: Descendant Explorer

No new tables. This spec consumes the existing query contract unchanged:

## Existing contract (unmodified): `DescendantsQueryInterface`

```php
interface DescendantsQueryInterface
{
    /** @return Collection<int, object{id:int, firstname:?string, surname:?string,
     *   sex:?string, father_id:?int, mother_id:?int, dod:?string, yod:?int,
     *   team_id:?int, photo:?string, dob:?string, yob:?int, degree:int, sequence:string}>
     */
    public function getDescendants(int $personId, int $maxDepth): Collection;
}
```

Resolved to the driver-specific implementation
(`MySqlDescendantsQuery`/`PgSqlDescendantsQuery`/`SQLiteDescendantsQuery`)
by the existing binding mechanism — this spec does not change the binding.

## Derived: Tree/List view model (not persisted)

`DescendantExplorer` transforms the flat `degree`-tagged collection into:

- **Tree shape**: nested structure keyed by `father_id`/`mother_id` back to
  the root, grouped by `degree`, each node privacy-filtered via
  `PersonPrivacy::isPubliclyVisible()` (spec 003) before rendering
  sensitive fields.
- **List shape**: the same flat collection, unmodified in structure, with
  `degree` rendered as "Generation N" and filterable client-side (small
  result sets) or server-side (larger sets, `$maxDepth`-bounded).

## State / Lifecycle

- `Explorer` Livewire component state: `personId`, `maxDepth` (FR-005,
  user-adjustable), `expandedNodeIds` (array, tracks which tree branches are
  currently shown), `view` (`'tree' | 'list'`). All ephemeral, not
  persisted server-side beyond the request/session.
