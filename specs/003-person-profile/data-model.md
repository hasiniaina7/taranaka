# Phase 1 Data Model: Public Person Profile

No new tables. Read-only projection over existing `Person`, `Couple`,
`lineage_person` (spec 001).

## Derived concept: Public Person Projection

Not a stored entity — the shape returned by `PersonPrivacy::publicFields(Person $person): array`:

| Field | Always shown? | Rule |
|---|---|---|
| firstname, surname, birthname, nickname | Yes | Never classified as sensitive. |
| photo | Yes (placeholder if null) | |
| lifespan (birth/death year) | Yes if deceased; year-only if living | Full `dob`/`dod` dates withheld for living per FR-003. |
| summary | Yes | Not classified as sensitive for MVP (biography text is expected to be shared deliberately by whoever writes it). |
| lineages | Yes | Via spec 001's `people()`/`lineages()` relation. |
| parents / partners / children | Yes (as linked references, each also privacy-filtered when visited) | |
| street/number/postal_code/city/province/state/country/phone | **No, if living & not opted in** | FR-003. |
| exact dob | **No, if living & not opted in** | Year-only permitted. |

## `PersonPrivacy` helper (interim, superseded by spec 007)

```php
final class PersonPrivacy
{
    public static function isLiving(Person $person): bool
    {
        return ! $person->isDeceased(); // reuses existing Person::isDeceased()
    }

    public static function isPubliclyVisible(Person $person): bool
    {
        return ! self::isLiving($person); // no opt-in mechanism yet — spec 007 adds it
    }
}
```

Spec 007 REPLACES the body of `isPubliclyVisible()` to also check an opt-in
flag; it does not change the call sites in `PublicProfile`/`PrivacyBanner`,
which already depend on this interface — this is the seam spec 007 plugs
into (see spec 007's own data-model.md).

## State / Lifecycle

None new — this spec observes `Person.dod`/`Person.yod` state, doesn't
introduce any.
