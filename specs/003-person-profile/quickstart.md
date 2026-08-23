# Quickstart: Validating the Public Person Profile

## 1. Guest views a deceased person (User Story 1)

1. In an incognito/signed-out browser session, visit `/people/{id}` for a
   deceased seeded person with parents/partner/children recorded.
2. **Expected**: name, lifespan, lineages, family links all render; no
   login redirect.

## 2. Living person is protected (User Story 2)

1. As a signed-out visitor, visit `/people/{id}` for a person with no
   `dod`/`yod`.
2. **Expected**: address/phone/exact dob absent from the raw HTML
   (`curl -s .../people/{id} | grep -c "<street value>"` returns 0); a
   `PrivacyBanner` is shown.
3. As an authenticated contributor with edit rights on that same person,
   visit the existing authenticated show route.
4. **Expected**: full data still visible there, unaffected (FR-008).

## 3. Family navigation (User Story 3)

1. From the profile in step 1, click a parent link, then a child link.
2. **Expected**: each click lands on that person's own public profile.

## 4. Not-found handling

1. Visit `/people/999999` (nonexistent).
2. **Expected**: styled not-found page, not a raw framework error.

## Automated coverage

```
vendor/bin/sail artisan test --compact --filter=PersonProfile
```
