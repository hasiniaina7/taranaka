# Quickstart: Validating Privacy Rules

## 1. Default protection across every surface (User Story 1)

1. Create a living person with address/phone/exact dob filled in.
2. Visit their public profile (003), a tree containing them (004/005), and
   a search result matching them (006).
3. **Expected**: none of the three sensitive fields appear on any of the
   four surfaces (SC-001) — run the shared cross-feature test suite rather
   than checking each manually.

## 2. Opt-in (User Story 2)

1. As the person's authorized editor, toggle "Rendre ce profil public."
2. **Expected**: name/lineage become visible publicly; address/phone remain
   withheld (still not separately opted in).
3. Attempt the same toggle as an unauthorized user. **Expected**: denied /
   control not rendered.

## 3. Death recorded (User Story 3)

1. Record a death date for a previously private person.
2. **Expected**: profile becomes public immediately, no separate publish
   step, `PrivacyToggle` shows the auto-public explanatory note.
3. Remove the death date. **Expected**: profile reverts to its prior
   opt-in state (private, unless it had been separately opted in before the
   death date was recorded).

## Automated coverage

```
vendor/bin/sail artisan test --compact --filter=Privacy
```
