# Feature Specification: Public Person Profile

**Feature Branch**: `003-person-profile`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Build a modern person profile page that any visitor (not just logged-in contributors) can open, showing a person's core facts, lineages, family (parents/partners/children), and quick actions to explore descendants/ancestors — respecting privacy rules for living people."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Visitor opens a deceased person's profile (Priority: P1)

A visitor with no account follows a link or search result to a deceased
person's profile and sees their name, lifespan, photo (if any), lineages,
parents, partner(s), and children.

**Why this priority**: This is the first genuinely public page in the
product (today, per the 000 audit, every `people/*` route requires
authentication) — it's the smallest slice that proves the "visitor" persona
from the product vision actually works end to end.

**Independent Test**: As a signed-out browser session, open a deceased
person's profile URL directly and verify the page renders with their data,
with no redirect to a login page.

**Acceptance Scenarios**:

1. **Given** a deceased person with parents, a partner, and children recorded,
   **When** a signed-out visitor opens their profile, **Then** the page shows
   name, lifespan (birth–death years), lineages, parents, partner(s), and
   children, each linking to their own profile.
2. **Given** a person with no photo, **When** their profile is viewed,
   **Then** the page shows a neutral placeholder instead of a broken image.

---

### User Story 2 - Living person's profile is protected (Priority: P1)

A visitor opens the profile URL of a person who is presumed living (no
recorded death). Sensitive details are withheld by default.

**Why this priority**: This is Constitution Principle II applied to the
first public page that exists — must ship in the same increment as User
Story 1, not after it, or there is a window where public profiles leak living
people's data.

**Independent Test**: As a signed-out visitor, open the profile of a person
with no `dod`/`yod`, and verify address/phone/exact birth date are absent and
a "private" indicator is shown instead.

**Acceptance Scenarios**:

1. **Given** a person with no recorded death date, **When** a signed-out
   visitor opens their profile, **Then** address, phone, and exact birth date
   are not rendered; the page indicates the profile is private/limited rather
   than showing nothing or erroring.
2. **Given** the same living person, **When** a contributor with edit rights
   on that person opens the profile while authenticated, **Then** they see
   the full data as today (this spec does not change the authenticated
   editing experience).

---

### User Story 3 - Navigate from a profile into family and lineages (Priority: P2)

A visitor on a person's profile clicks a parent, a child, a partner, or a
lineage tag and lands on that entity's own page.

**Why this priority**: Turns a single profile into the start of real
browsing — the core "start from one person, discover the graph" behavior
described in the product vision — but only matters once individual profiles
(User Story 1/2) exist.

**Independent Test**: From a person's profile, click each family member link
and each lineage tag, and verify each leads to the correct corresponding
page.

**Acceptance Scenarios**:

1. **Given** a person with two recorded parents, **When** a visitor clicks a
   parent's name, **Then** they land on that parent's own profile.
2. **Given** a person attached to two lineages, **When** a visitor clicks
   either lineage tag, **Then** they land on that lineage's page (spec 001).

---

### Edge Cases

- What happens when a profile is requested for a person who does not exist or
  has been soft-deleted? → A standard "not found" page is shown, not a server
  error, and not information about whether the ID ever existed if that would
  leak private data.
- What happens when a person has no parents recorded at all? → The parents
  section shows an empty/unknown state, not an error.
- What happens when a visitor requests a private (living) person's profile by
  guessing/incrementing an ID? → Same private/limited view as User Story 2;
  no different response reveals more than the defined public fields.
- What happens when a person belongs to zero lineages? → The lineages section
  shows an empty state rather than being hidden entirely (so it's clear the
  data is simply absent, not an error).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST expose a person profile page reachable without
  authentication.
- **FR-002**: System MUST render, for a deceased person, name, lifespan
  (birth/death year or date depending on precision available), photo or
  placeholder, lineages, parents, partner(s), and children.
- **FR-003**: System MUST withhold address, phone, and exact birth date for
  any person without a recorded death date, per the privacy rule (spec 007;
  until spec 007 ships, these fields MUST simply not be rendered publicly at
  all — Constitution Principle VIII).
- **FR-004**: System MUST clearly indicate on a living person's public profile
  that the view is limited/private, rather than silently omitting fields with
  no explanation.
- **FR-005**: System MUST link each family member (parent/partner/child) shown
  on a profile to that member's own profile.
- **FR-006**: System MUST link each lineage shown on a profile to that
  lineage's page.
- **FR-007**: System MUST return a not-found response for a nonexistent or
  soft-deleted person, without distinguishing (in the response) between "does
  not exist" and "is private," where such distinction could aid enumeration
  of private records.
- **FR-008**: An authenticated contributor with edit rights on a person MUST
  continue to see that person's full existing profile/edit experience,
  unaffected by the new public view.

### Key Entities

- **Person**: existing entity (see 000 audit); this spec adds a public,
  privacy-filtered *read* representation, not new fields.
- **Lineage**: existing entity from spec 001, referenced (not modified) here
  for the profile's lineage tags.

### Team/Lineage scope interaction *(Constitution Principle VII)*

The public profile is NOT team-scoped: a visitor is not a member of any team,
so the existing `team` global scope (which only applies when `auth()->user()`
is present) does not restrict this view today. This spec MUST introduce an
explicit privacy/visibility filter for guest access (FR-003/FR-004) since the
absence of a team scope is not, by itself, a privacy control.

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- `GET /people/{person}` — new **public** route (no `auth:sanctum` middleware),
  distinct from the existing authenticated `people/{person}/show` route used
  by contributors today. Both may ultimately resolve to the same underlying
  data, but the public route MUST NOT require authentication.

### Livewire Components

- `PersonProfile` (public) — header (photo or placeholder, name, lifespan),
  lineage tag row (links to spec 001 lineage pages), family panel with three
  sub-sections (parents, partners, children) each rendered as linkable cards,
  and a quick-actions bar ("Explorer les descendants" / "Explorer les
  ascendants" linking to specs 004/005).
- `PrivacyBanner` (shared component, reused by specs 004/005/006/007) —
  rendered at the top of a living, non-opted-in person's profile: "Profil
  privé — informations limitées."
- Mobile-first layout per Tailwind conventions already in use; family panel
  collapses to a stacked single-column layout below the `md` breakpoint.

### Key Screen States

- **Full public profile** (deceased or opted-in living person): all sections
  populated per FR-002.
- **Privacy-limited profile** (living, non-opted-in): `PrivacyBanner` shown,
  sensitive fields absent from both the rendered page and the underlying
  Blade/Livewire payload (not just CSS-hidden).
- **Missing photo**: neutral placeholder avatar, never a broken image icon.
- **Empty family sections**: "Aucun parent connu" / "Aucun partenaire
  enregistré" / "Aucun enfant enregistré" shown individually per empty
  sub-section rather than hiding the section entirely (User Story 3 depends
  on these sections being present to click into).
- **Not found**: dedicated 404-style page, visually consistent with the rest
  of the public site, not the framework's raw error page.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A signed-out visitor can reach and read a deceased person's full
  public profile without any authentication prompt.
- **SC-002**: 0% of living persons' address, phone, or exact birth date appear
  in the rendered HTML of their public profile for a signed-out visitor.
- **SC-003**: From any person's profile, a visitor can reach any directly
  linked family member's profile in one click.

## Assumptions

- "Presumed living" = no `dod` and no `yod` recorded. A future refinement
  (e.g. age-based presumption after N years with no death recorded) is
  possible but not required for MVP.
- The visual/interaction design of the profile (layout, sections order) is
  intentionally not specified here — it's a business-behavior spec, not a UI
  spec; implementation follows existing TallStackUI conventions.
- Media/documents beyond the profile photo (spec F011 in the product vision)
  are out of scope for this spec.
