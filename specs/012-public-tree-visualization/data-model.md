# Data Model: Public Tree Visualization

No new persisted entities or migrations. This spec adds one client-side
view-model shape and extends two existing server-side payload arrays.

## Existing entities reused (unchanged)

- **Person** (`app/Models/Person.php`): `id`, `firstname`, `surname`,
  `sex`, `yob`, `yod`, `photo`, `team_id`.
- **Couple** (`app/Models/Couple.php`): `person1_id`, `person2_id`,
  `date_start`, `date_end`, `is_married`, `has_ended`. Accessed via
  `Person::couples()` (`HasManyMerged`, already loads `person1`/`person2`).

## Extended server-side payload: descendant/ancestor tree node

Both `Livewire\People\Descendants\Tree::tree()` and
`Livewire\People\Ancestors\Tree::tree()` gain two additional fields per
node, on top of their existing shape:

| Field | Type | Source | Notes |
|---|---|---|---|
| `photo_url` | `string\|null` | `Storage::disk('photos')->url(...)` if the file exists, else `null` | FR-010. `null` renders the placeholder silhouette client-side. Suppressed (forced `null`) for privacy-limited living nodes (FR-004). |
| `partner_ids` | `list<int>` | `Person::couples()` on the node's person, excluding the node itself | FR-009. One id per recorded couple (a remarried person can have more than one). |

Existing fields per tree (unchanged, listed for reference):

- Descendant node (`BuildDescendantNodes::execute()`): `id`, `name`,
  `degree`, `sequence`, `parent_sequence`, `birth_year`, `death_year`,
  `is_living`, `lineages`.
- Ancestor node (`Ancestors\Tree::buildNode()`): `id`, `firstname`,
  `surname`, `father_id`, `mother_id`, `yob`, `degree`, `depth`, `living`,
  `expanded`, `canExpand`.

## New client-side view-model: `family-chart` input

Assembled in JavaScript (not persisted, not sent by the server in this
exact shape) from the extended payload above, one adapter per tree
direction (`buildFamilyChartData(descendantPayload)` /
`buildFamilyChartData(ancestorPayload)`), both producing `family-chart`'s
expected node format:

```text
{
  id: string,               // Person id, stringified
  data: {
    label: string,           // full name, or "Nom inconnu" fallback
    photo: string|null,      // photo_url, or null for placeholder
    isLiving: boolean,
    profileUrl: string,      // route('public.people.show', $id)
  },
  rels: {
    children: list<string>,  // child node ids
    spouses: list<string>,   // partner_ids, stringified
    father: string|null,     // ancestor tree only
    mother: string|null,     // ancestor tree only
  }
}
```

## State transitions

None — this is a read-only, presentation-layer feature. Branch
expand/collapse state (`expandedNodeIds`, `loadedNodeIds`) already exists in
both Livewire components and is unchanged; the client-side renderer simply
re-reads the updated payload after each Livewire response.
