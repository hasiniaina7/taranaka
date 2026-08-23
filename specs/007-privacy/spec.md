# Feature Specification: Privacy Rules for Living People

**Feature Branch**: `007-privacy`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Formalize the privacy rule that has been assumed by specs 003, 004, 005, and 006: living people are protected by default across every public surface, with a single reusable rule instead of each feature reimplementing its own check."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A living person's sensitive data never appears publicly by default (Priority: P1)

Anywhere a person can be shown publicly (profile, tree node, search result),
a living person's sensitive fields are consistently withheld without each
feature needing its own logic.

**Why this priority**: Specs 003/004/005/006 already assume this rule
exists; without a single authoritative implementation, each feature risks
diverging or a future feature forgetting to apply it. This is the single
highest-consequence rule in the product (Constitution Principle II).

**Independent Test**: Create a living person with address, phone, and exact
birth date filled in. Verify none of those three fields appear in the raw
HTML response of their profile, any tree containing them, or any search
result matching them, as a signed-out visitor.

**Acceptance Scenarios**:

1. **Given** a living person with address, phone, and exact birth date
   recorded, **When** any public surface displays them, **Then** none of
   those three fields appear in the response.
2. **Given** the same living person, **When** the surface is a tree/list
   context rather than their own profile, **Then** the same withholding
   applies identically (no surface is exempt).

---

### User Story 2 - A living person can opt in to public visibility (Priority: P2)

The person themself (if they hold an account) or an authorized contributor
explicitly marks a living person as publicly visible, after which the
default withholding no longer applies to that person.

**Why this priority**: The product vision explicitly allows for exceptions
("visitor can eventually see..."); without an opt-in mechanism, the privacy
rule is a permanent ceiling rather than a sensible default, which reduces the
platform's usefulness for people who want to be found.

**Independent Test**: Mark a living person as publicly visible via the opt-in
action, then verify their non-sensitive fields (name, lifespan-in-progress
indicator, lineage) become visible while still-sensitive fields (exact
address) remain withheld unless individually opted in.

**Acceptance Scenarios**:

1. **Given** a living person marked as publicly visible, **When** a visitor
   opens their profile, **Then** name and lineage are shown; address and
   phone remain withheld unless separately marked visible.
2. **Given** a living person NOT marked as publicly visible, **When** any
   contributor without edit rights on that person attempts to toggle their
   visibility, **Then** the action is denied.

---

### User Story 3 - Death is recorded and the person's visibility updates automatically (Priority: P2)

A contributor records a death date for a previously living, privacy-protected
person.

**Why this priority**: This is the normal lifecycle transition the entire
privacy model is built around — it must work automatically, not require a
separate manual "make public" step.

**Independent Test**: Record a death date for a person whose profile was
previously private, then verify their profile becomes publicly visible
(subject to any explicit opt-out, if one exists) without further action.

**Acceptance Scenarios**:

1. **Given** a living, privacy-protected person, **When** a contributor
   records their death date, **Then** the person's public profile becomes
   visible on next view, without a separate publish step.

---

### Edge Cases

- What happens when a death date is recorded but is clearly implausible (e.g.
  in the future)? → Out of scope for this spec; standard field validation on
  `dod`/`yod` already governs this, unrelated to the privacy rule itself.
- What happens when only a death year (`yod`) is known, not a full date? →
  Treated identically to a full `dod` for privacy purposes — presence of
  either is sufficient to be considered "not living" (matches
  `Person::isDeceased()` already in the codebase).
- What happens when a living person is later found to have been recorded in
  error as deceased? → Reversing `dod`/`yod` MUST re-apply the default
  privacy protection immediately, symmetric with User Story 3.
- What happens when an opted-in living person wants to revoke visibility? →
  MUST be possible; revocation re-applies default protection immediately.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST provide one single, reusable privacy-evaluation
  rule (not per-feature duplicated logic) that every public surface
  (profile, tree, list, search) consults before rendering a person's data.
- **FR-002**: The rule MUST classify a person as "living" when neither `dod`
  nor `yod` is recorded, and "not living" otherwise.
- **FR-003**: For a "living" and non-opted-in person, the rule MUST withhold
  at minimum: street/number, postal code, city, province/state, country,
  phone, exact date of birth (year-only MAY be shown per implementation
  choice, but full date MUST NOT).
- **FR-004**: System MUST provide an explicit, auditable opt-in action that
  marks a living person as publicly visible, restricted to that person's
  account (if any) or a contributor with edit rights on that person.
- **FR-005**: System MUST provide a way to revoke that opt-in, which
  immediately re-applies default protection.
- **FR-006**: System MUST automatically lift default protection when a death
  date is recorded, without requiring a separate publish action.
- **FR-007**: System MUST automatically re-apply default protection if a
  previously recorded death date is removed or corrected to indicate the
  person is not deceased.
- **FR-008**: Every existing and future public-facing feature (specs 003,
  004, 005, 006, and any added later) MUST consult this rule rather than
  implement its own living/private check.

### Key Entities

- **Person**: gains a privacy state derived from `dod`/`yod` plus an explicit
  opt-in flag; no change to the person's core biographical fields.
- **Privacy Decision**: not a stored entity by itself in this spec — a
  computed rule (living vs. not-living, opted-in vs. not) applied at render
  time; auditability of opt-in/opt-out changes is expected to flow through
  the existing Activitylog mechanism already attached to `Person`.

### Team/Lineage scope interaction *(Constitution Principle VII)*

Privacy visibility is orthogonal to `team_id`/`Lineage` membership: whether
a person is *reachable* by a viewer (team scope, spec 002; lineage
membership, spec 001) is decided before this rule ever runs, and whether a
reachable person's *sensitive fields* are shown is decided entirely by
`PersonPrivacy::isPubliclyVisible()` / `isLiving()` — never by which team
or lineage the viewer belongs to. A living, non-opted-in person is withheld
identically for a guest, a contributor in a different team, and a
contributor in the person's own team viewing the public surface (specs
003/004/005/006); team/lineage membership grants no bypass of this rule.
The one exception is the existing authenticated `people.show`-family
routes (FR-008), which remain unaffected by this spec and continue to show
full data to a contributor with edit rights, per today's existing
team-scoped authorization — that path is untouched, not a privacy bypass
introduced here.

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- No dedicated new public page. This spec centralizes a rule consumed by
  specs 003/004/005/006's existing pages, plus one new authenticated control
  on the person edit screen.

### Livewire Components

- `PrivacyBanner` — **already exists** as a stateless Blade component
  (`app/View/Components/PrivacyBanner.php`, spec 003), not introduced by
  this spec. It renders its "Profil privé — informations limitées"
  indicator purely from a boolean `shown` prop, fed by
  `PersonPrivacy::isPubliclyVisible()`. This spec extends
  `isPubliclyVisible()`'s underlying rule (FR-002/FR-003) but MUST NOT
  recreate `PrivacyBanner` as a new component (Livewire or otherwise) —
  the existing Blade component's call sites in specs 003/004/005/006
  automatically pick up the extended rule with zero changes to
  `PrivacyBanner` itself.
- `PrivacyToggle` — new control on the person edit screen (`edit-profile` or
  a dedicated "Confidentialité" panel), visible only to a user with edit
  rights on that person (FR-004): a switch "Rendre ce profil public" with a
  short explanatory line, disabled/hidden entirely for deceased persons
  (toggle is meaningless once FR-006 auto-lifts protection).
- Recording a death date on the existing person edit form (`edit-death`)
  triggers, on save, an inline confirmation that the profile is now public
  (User Story 3) — no separate publish screen.

### Key Screen States

- **Toggle on / off**: `PrivacyToggle` reflects current state immediately on
  change (optimistic UI acceptable since the action is same-request), and
  every public surface (spec 003 profile, spec 004/005 tree nodes, spec 006
  search results) reflects the new state on next load — no stale cache
  indicator shown to the editor.
- **Unauthorized toggle attempt**: hidden/disabled for any user without edit
  rights on that person (User Story 2, Acceptance Scenario 2) — not merely
  disabled client-side; the control simply does not render for them.
- **Death recorded**: `PrivacyToggle` becomes disabled with a note ("Ce
  profil est désormais public — personne décédée") reflecting FR-006/FR-007's
  automatic behavior.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 0% of living, non-opted-in persons' sensitive fields appear on
  any public surface, verified across all of specs 003/004/005/006 with a
  single shared test suite rather than four separate ad hoc checks.
- **SC-002**: Recording a death date makes a person's profile publicly
  visible within the same request cycle, with zero manual follow-up steps.
- **SC-003**: An opt-in or opt-out action takes effect immediately (next page
  view reflects the new state, no caching delay beyond standard page load).

## Assumptions

- This spec formalizes and centralizes a rule that specs 003/004/005/006
  already assumed; it does not introduce new public surfaces itself.
- The exact set of "sensitive fields" (FR-003) may be refined during
  implementation but MUST NOT shrink below what's listed without an explicit
  constitution amendment, per Constitution Principle VIII.
- Granular per-field opt-in (e.g. "show my birth year but not my birth day")
  is a reasonable future refinement; the MVP opt-in (User Story 2) is
  coarse-grained (on/off) unless implementation finds the granular version
  equally cheap.
