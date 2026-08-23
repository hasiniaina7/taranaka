# Quickstart: Validating Contributions and Moderation

## 1. Propose a correction (User Story 1)

1. As a registered user without edit rights on a person, open their profile
   and click the pencil icon next to a field.
2. Submit a proposed new value.
3. **Expected**: proposal recorded with author/timestamp/old/new value; the
   person's live data is unchanged.

## 2. Moderator review (User Story 2)

1. As a moderator, open `/moderation/contributions`, open the proposal from
   step 1.
2. Accept it. **Expected**: person's data updates to match; proposal marked
   accepted with moderator + timestamp.
3. Create a second proposal, reject it with a reason. **Expected**: person's
   data unchanged; proposal marked rejected with the reason visible.

## 3. Propose a new person (User Story 3)

1. From an existing person's family panel, propose a new child.
2. **Expected**: proposal queued, no person record created yet.
3. Moderator accepts. **Expected**: new person created and linked as a
   child.

## 4. Traceability and notification

1. Check `MyContributions` for the proposal author.
2. **Expected**: status updates to reflect the moderator's decision, reason
   visible if rejected.

## Automated coverage

```
vendor/bin/sail artisan test --compact --filter=Contributions
```
