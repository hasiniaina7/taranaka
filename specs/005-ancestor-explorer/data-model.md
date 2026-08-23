# Phase 1 Data Model: Ancestor Explorer

No new tables. Mirrors spec 004's data-model.md.

## Existing contract (unmodified): `AncestorsQueryInterface`

```php
interface AncestorsQueryInterface
{
    /** @return Collection<int, object{id:int, firstname:?string, surname:?string,
     *   sex:?string, father_id:?int, mother_id:?int, dod:?string, yod:?int,
     *   team_id:?int, photo:?string, dob:?string, yob:?int, degree:int, sequence:string}>
     */
    public function getAncestors(int $personId, int $maxDepth): Collection;
}
```

Resolved via the existing driver-selection mechanism, unmodified.

## Derived: Tree/List view model (not persisted)

Same shape as spec 004, traversing `father_id`/`mother_id` upward instead
of downward. Additional transform: for any node at `degree < maxDepth`
missing a `father_id` or `mother_id`, `AncestorTree` inserts a rendering-only
placeholder slot (FR-006) — never a database row (see research.md).

## State / Lifecycle

- `Explorer` Livewire component state: identical shape to spec 004's
  (`personId`, `maxDepth`, `expandedNodeIds`, `view`), ephemeral.
