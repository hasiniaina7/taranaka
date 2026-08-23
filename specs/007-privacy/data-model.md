# Phase 1 Data Model: Privacy Rules for Living People

## Modified: `people` table

| Field | Type | Rules |
|---|---|---|
| is_publicly_visible | boolean, default `false` | NEW, nullable not required (has a default). Additive-only migration — no existing column touched (Constitution Principle IX). |

## Modified: `PersonPrivacy` (from spec 003)

```php
final class PersonPrivacy
{
    public static function isLiving(Person $person): bool
    {
        return ! $person->isDeceased(); // unchanged
    }

    public static function isPubliclyVisible(Person $person): bool
    {
        return ! self::isLiving($person) || (bool) $person->is_publicly_visible;
    }

    public static function publicFields(Person $person): array
    {
        // unchanged signature (spec 003 seam); body now branches on the
        // updated isPubliclyVisible() for the sensitive-field set.
    }
}
```

No call site in specs 003/004/005/006 changes — this is the entire point
of the seam spec 003 reserved.

## New: `PersonPolicy::togglePrivacy`

```php
public function togglePrivacy(User $user, Person $person): bool
{
    return $this->update($user, $person); // reuses spec 002's ownership rule verbatim
}
```

## State / Lifecycle

```text
Person.dod/yod null, is_publicly_visible=false  → PRIVATE (default)
Person.dod/yod null, is_publicly_visible=true   → PUBLIC (opt-in, User Story 2)
Person.dod/yod set (any value)                  → PUBLIC (auto, User Story 3, regardless of is_publicly_visible)
Person.dod/yod reverted to null                 → back to is_publicly_visible's stored value (Edge Cases symmetry)
```

`is_publicly_visible` itself is never auto-modified by the death-date
observer — only the *effective* `isPubliclyVisible()` result changes,
per research.md's decision.
