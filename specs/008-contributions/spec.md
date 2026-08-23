# Feature Specification: Contributions and Moderation

**Feature Branch**: `008-contributions`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Let registered users propose corrections, new people, or new relationships without directly editing data they don't own, and let moderators review, accept, or reject those proposals, with full traceability of who proposed what and why."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A registered user proposes a correction (Priority: P1)

A registered user (not a contributor with direct edit rights on that person)
notices an error on a public profile and submits a proposed correction with
the field, old value, and new value.

**Why this priority**: This is what makes the platform collaborative rather
than read-only for the public — the single most important behavior
distinguishing it from the current team-locked editing model, and the
mechanism spec 002 (FR-004) relies on for cross-team changes.

**Independent Test**: As a registered user with no edit rights on a given
person, submit a correction proposal for one field and verify the person's
live data is unchanged until a moderator acts.

**Acceptance Scenarios**:

1. **Given** a registered user viewing a public profile, **When** they submit
   a proposed correction to one field, **Then** the proposal is recorded with
   author, timestamp, old value, and new value, and the live profile is
   unchanged.
2. **Given** a user without edit rights on a person, **When** they attempt to
   edit that person directly (bypassing the proposal flow), **Then** the
   system denies direct edit and directs them to the proposal flow instead.

---

### User Story 2 - A moderator reviews and decides on a proposal (Priority: P1)

A moderator opens a queue of pending proposals, reviews one, and either
accepts it (applying the change) or rejects it (with a reason).

**Why this priority**: Without review, proposals are inert — this is the
other half of the same workflow and must ship together with User Story 1 for
either to deliver value.

**Independent Test**: As a moderator, open the pending queue, accept one
proposal, and verify the underlying person's data changes to match; reject a
second proposal and verify the person's data is unchanged and the proposal is
marked rejected with the given reason.

**Acceptance Scenarios**:

1. **Given** a pending proposal, **When** a moderator accepts it, **Then**
   the target person's data is updated to the proposed value, and the
   proposal is marked accepted with the moderator and timestamp recorded.
2. **Given** a pending proposal, **When** a moderator rejects it with a
   reason, **Then** the target person's data is unchanged, and the proposal
   is marked rejected with the moderator, timestamp, and reason recorded.

---

### User Story 3 - A contributor proposes a brand-new person or relationship (Priority: P2)

A registered user proposes an entirely new person (e.g. a previously unknown
child) or a new relationship (e.g. a marriage) rather than a correction to an
existing field.

**Why this priority**: Extends the same trust/review mechanism to additions,
not just corrections — necessary for the platform to grow through community
contribution, but naturally follows once correction proposals (User Story
1/2) work.

**Independent Test**: Propose a new person as a child of an existing person,
have a moderator accept it, and verify the new person now exists and is
correctly linked as a child.

**Acceptance Scenarios**:

1. **Given** an existing person with no recorded children, **When** a
   registered user proposes a new child with basic biographical data,
   **Then** the proposal is queued for review without creating the person
   record yet.
2. **Given** that proposal is accepted, **When** the moderator confirms,
   **Then** the new person is created and linked as a child of the existing
   person.

---

### Edge Cases

- What happens when two proposals target the same field of the same person
  at the same time? → Both proposals MUST remain independently visible to
  the moderator; accepting one does not silently invalidate the other — the
  moderator explicitly rejects the superseded one (with the reason "superseded
  by another accepted proposal" or similar).
- What happens when the target person is deleted or the target field no
  longer exists by the time a moderator reviews the proposal? → The proposal
  MUST be shown as no longer applicable, not silently dropped, so the
  moderator can explicitly close it out.
- What happens when a proposal author is later deleted or deactivated? → The
  proposal's historical author reference MUST be retained for traceability
  even if the account is deactivated.
- What happens when a contributor with direct edit rights on a person
  chooses to use the proposal flow anyway rather than editing directly? →
  Allowed; the proposal flow is always available, not restricted to users
  lacking direct rights, though direct edit remains the faster path for
  those who have it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow a registered user to submit a proposal
  containing: target entity (person or relationship), action (correction,
  new person, new relationship), old value (if applicable), proposed new
  value, and optional justification/note.
- **FR-002**: System MUST NOT apply a proposal's change to live data until a
  moderator explicitly accepts it.
- **FR-003**: System MUST prevent a user without edit rights on a person from
  editing that person directly, and MUST route them to the proposal flow
  instead.
- **FR-004**: System MUST provide moderators a queue of pending proposals,
  filterable at minimum by status (pending/accepted/rejected).
- **FR-005**: System MUST allow a moderator to accept a proposal, which
  applies the proposed change to live data and records moderator identity and
  timestamp.
- **FR-006**: System MUST allow a moderator to reject a proposal with a
  required reason, without applying any change, and record moderator
  identity, timestamp, and reason.
- **FR-007**: System MUST retain full traceability of every proposal
  (author, timestamp, old/new value, decision, decider, decision timestamp,
  reason if rejected) regardless of the proposal's final status.
- **FR-008**: System MUST support proposals for new person creation and new
  relationships (couple, parent/child), not only field corrections on
  existing people.
- **FR-009**: System MUST notify (at minimum, make visible to) the proposal's
  author when a decision is made on their proposal.

### Key Entities

- **Contribution / Proposal**: author, target entity type + id (nullable for
  "new person" proposals), action type, old value, new value, justification,
  status (pending/accepted/rejected), reviewer, reviewed-at, rejection
  reason.

### Team/Lineage scope interaction *(Constitution Principle VII)*

This spec is the designated mechanism (per spec 002 FR-004) for a user
without direct edit rights on a person — including a contributor from a
different team, or lineage-only community members — to affect that person's
data. Direct edit permission (`team_id` ownership) is unchanged by this
spec; what changes is that lack of direct permission now has a legitimate
path forward (propose) instead of being a dead end.

Because `Person`/`Couple` carry a global `team` Eloquent scope (per the 000
audit) that filters queries to the *current user's* team, resolving a
Contribution's `target` — both when the proposal is created (FR-001, a
contributor proposing a change to someone outside their team) and when a
moderator loads it for review (`ModerationQueue`/`ContributionReview`) —
MUST explicitly bypass that scope (e.g.
`Person::withoutGlobalScope('team')->findOrFail($id)`). Without this, the
target silently fails to resolve (404/empty) for exactly the cross-team
case this spec exists to serve. This bypass applies only to *resolving*
the target for display/proposal purposes — it grants no additional
*write* permission; accepting a contribution still goes through the
target's normal `save()`/authorization path (see Contribution lifecycle).

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- `GET/POST /people/{person}/propose` — proposal form (User Story 1/3),
  reachable both as a standalone route and inline from the public profile.
- `GET /contributions` — authenticated user's own proposal history (status,
  timestamps).
- `GET /moderation/contributions` — moderator queue (User Story 2, FR-004).
- `GET /moderation/contributions/{contribution}` — single proposal review
  screen.

### Livewire Components

- `ProposeChange` — on the public/authenticated profile view, each editable
  field a non-owning registered user sees gets an inline pencil icon
  ("Suggérer une correction") that opens this component pre-filled with the
  current value; submitting requires the new value and an optional
  justification note (FR-001).
- `ProposeNewPerson` / `ProposeRelationship` — forms reachable from a
  person's family panel ("Proposer un enfant", "Proposer une union") for
  User Story 3, structurally similar to the existing `PersonForm` but
  submitting to the proposal queue instead of saving directly.
- `ContributionQueue` (moderator) — TallStackUI table: author, target,
  action type, submitted date, status filter (FR-004).
- `ContributionReview` (moderator) — side-by-side/diff view of old vs.
  proposed value, with Accept and Reject actions; Reject requires a reason
  field before the action is enabled (FR-006).
- `MyContributions` — authenticated user's own list with status badges
  (pending/accepted/rejected) and, for rejected items, the visible reason
  (FR-009).

### Key Screen States

- **Field-level propose affordance**: pencil icon appears only for fields the
  viewer cannot edit directly (per spec 002's ownership rule) — a
  contributor with direct rights sees the normal edit control instead, not
  both.
- **Pending proposal submitted**: confirmation toast + entry appears
  immediately in `MyContributions` with status "En attente."
- **Superseded/no-longer-applicable proposal** (Edge Cases): shown in the
  moderator queue with a distinct visual state ("Cible modifiée entre-temps")
  rather than looking identical to a normal pending item.
- **Decision made**: `MyContributions` entry updates to Accepted (green) or
  Rejected (red, with reason shown inline) without the user needing to open
  the item to see the outcome.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of proposed changes are applied to live data only after
  an explicit moderator accept action — zero proposals bypass review.
- **SC-002**: A moderator can review and decide on a pending proposal
  (accept or reject) in under 1 minute for a single-field correction.
- **SC-003**: 100% of accepted and rejected proposals retain a complete,
  queryable audit trail (author, reviewer, both values, timestamps).

## Assumptions

- This spec reuses the existing Activitylog infrastructure already attached
  to `Person`/`Couple` for *technical* change history, but introduces a
  distinct Contribution/Proposal concept for the *pre-application review*
  workflow — the two are complementary, not the same mechanism (see 000
  audit §1.1).
- Role definitions (registered user, contributor, moderator) reuse the
  persona definitions already established in the product cadrage document;
  this spec does not redefine roles, only the proposal/review workflow
  between them.
- Automated/AI-assisted proposal scoring or triage is explicitly out of
  scope for MVP; all review is manual.
