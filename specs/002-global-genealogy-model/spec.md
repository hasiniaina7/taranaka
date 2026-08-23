# Feature Specification: Global Genealogy Model

**Feature Branch**: `002-global-genealogy-model`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Move away from the current model where every person and couple is locked to a single team/tree, so that people connected across different lineages (via marriage or shared descendants) are visibly part of one connected graph, while keeping team-based edit permissions working."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Two previously separate families connect through a marriage (Priority: P1)

Person A (lineage RAKOTO) marries Person B (lineage RABE). A contributor
records this union. Afterward, browsing from Person A's descendants naturally
leads into people who are also part of the RABE lineage, without any manual
"merge the two trees" step.

**Why this priority**: This is the product's central promise (see the
project's cadrage document, §1.1–1.4) — genealogy as a connected graph, not
isolated per-family trees. Today this is blocked at the data level (single
`team_id` per person/couple), so it must be unblocked before descendant/
ancestor exploration (specs 004/005) can demonstrate real cross-family value.

**Independent Test**: Create a couple between a RAKOTO-lineage person and a
RABE-lineage person, add a shared child, and verify the child appears when
exploring descendants from either the RAKOTO or the RABE side.

**Acceptance Scenarios**:

1. **Given** Person A in lineage RAKOTO and Person B in lineage RABE, **When**
   a contributor records a couple between them and a shared child, **Then**
   the child is reachable via descendant exploration starting from Person A
   AND from Person B, without any duplicate person records.
2. **Given** a contributor with edit rights on one team only, **When** they
   view a person or couple that originated from a different team, **Then**
   they can view it (if visible per the applicable privacy rule) but cannot
   edit it unless granted appropriate rights.

---

### User Story 2 - Existing team-scoped editing keeps working (Priority: P1)

A contributor who only works within their own team continues to add, edit,
and manage people and couples exactly as before, without needing to
understand or interact with the new cross-team graph concept.

**Why this priority**: Zero-regression requirement — this spec changes the
foundation underneath the app; existing daily workflows must not break.

**Independent Test**: Run the existing people-management flows (add person,
edit profile, add couple) inside a single team, exactly as before this spec,
and confirm all existing Pest tests for these flows still pass.

**Acceptance Scenarios**:

1. **Given** a contributor working inside their current team, **When** they
   create, edit, or delete a person or couple, **Then** the behavior and
   scoping is functionally identical to before this spec from that
   contributor's point of view.

---

### User Story 3 - Determine who may edit a person that spans lineages (Priority: P2)

A person is attached to lineages from two different teams' contributor
groups. A contributor from either team attempts to edit that person's core
profile.

**Why this priority**: Directly follows from decoupling identity (Lineage)
from permission (Team) — without an explicit rule, this is undefined
behavior and a real risk of edit conflicts or unauthorized changes.

**Independent Test**: Attach one person to lineages owned by two different
teams, then attempt an edit from each team's contributor account and verify
the permission outcome matches the defined rule.

**Acceptance Scenarios**:

1. **Given** a person attached to lineages from Team X and Team Y, **When** a
   contributor from Team X attempts to edit that person's core biographical
   data, **Then** the system applies a single, well-defined ownership rule
   (see FR-004) rather than silently allowing or silently blocking based on
   incidental query order.

---

### Edge Cases

- What happens to a person's `team_id` when they get attached to a lineage
  owned by a different team via a shared child or marriage? → `team_id`
  itself does not change automatically; it continues to reflect who
  originally created/owns the record for edit-permission purposes (FR-004).
- What happens to the existing global scope on `Person`/`Couple` when a
  developer/administrator needs to see across all teams (already supported
  today via `is_developer`)? → That existing bypass MUST continue to work
  unchanged.
- What happens when two contributors from different teams both try to edit
  the same cross-lineage person at the same time? → Out of scope for this
  spec (standard last-write-wins via existing update behavior); concurrent-edit
  conflict resolution is a possible future refinement, not required for MVP.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow a `Couple` to link two people regardless of
  whether those people originated in the same team.
- **FR-002**: System MUST allow descendant/ancestor traversal (existing CTE
  queries) to cross team boundaries when a couple or parent/child link
  connects people from different teams, so the graph is genuinely connected.
- **FR-003**: System MUST continue to enforce today's team-scoped list/search
  views for contributors who have not been given cross-team visibility,
  UNLESS the person/couple being viewed is reachable through an explicit
  relationship (couple or parent/child) from a person already visible to that
  contributor.
- **FR-004**: System MUST define and enforce a single ownership rule for edit
  permission on a person/couple: the team recorded in that record's `team_id`
  is the only team whose contributors may edit it directly, regardless of how
  many lineages it is attached to. Cross-team changes MUST go through the
  contribution/moderation flow (spec 008), not direct edit.
- **FR-005**: System MUST NOT change the meaning of `is_developer` full
  cross-team access already present in the codebase.
- **FR-006**: System MUST preserve all existing team-scoped CRUD behavior for
  contributors operating entirely within one team (zero observable regression).
- **FR-007**: System MUST allow reading a person's or couple's `team_id`
  without exposing it as an editable field to contributors outside that team.

### Key Entities

- **Person** / **Couple**: unchanged in shape from today, except that the
  existing `team` global scope's *filtering* behavior is refined per FR-003 —
  it no longer strictly means "cannot see," it means "cannot see unless
  reachable through a relationship from something already visible."
- **Team**: keeps its existing role as the owning/permission boundary for
  direct edits (FR-004); its role as "the container of an entire tree" is
  superseded by `Lineage` (spec 001) for identity/browsing purposes.

## UI & Interface Requirements *(mandatory)*

### Routes / Pages

- No new routes. This spec changes what existing pages (`people/{person}`,
  descendant/ancestor explorers in specs 004/005, `PersonForm` edit screens)
  are allowed to show and edit; it is surfaced entirely through those
  existing/upcoming screens.

### Livewire Components

- `PersonShow` / person edit screens gain an ownership indicator: when the
  viewed person's `team_id` differs from the viewer's current team, render a
  read-only badge ("Géré par une autre équipe") and replace edit
  buttons/fields with a disabled state plus a "Proposer une modification"
  call-to-action linking into the spec 008 contribution flow.
- `DescendantTree` / `AncestorTree` (specs 004/005) nodes belonging to a
  different team than the viewer's current team render a small cross-team
  badge on the node, so a contributor visually understands why edit actions
  are unavailable on that branch.

### Key Screen States

- **Read-only cross-team view**: person/couple belonging to another team is
  fully viewable (subject to privacy rules) but every edit affordance
  (fields, buttons, inline "edit" pencils) is disabled/hidden and replaced by
  the propose-a-change CTA (FR-004).
- **Own-team view**: unchanged from current behavior — full edit UI, no
  badge, no CTA (User Story 2, zero-regression requirement).
- **Traversal crossing teams**: when a descendant/ancestor tree crosses from
  one team's data into another's mid-traversal (User Story 1), the tree
  renders as one continuous branch — no visual "seam," no separate loading
  step, no "switch team to continue" interruption.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of existing team-scoped CRUD Pest tests pass unchanged
  after this spec is implemented.
- **SC-002**: A descendant/ancestor query starting from a person in Team X and
  passing through a couple with a person in Team Y returns the full connected
  chain in a single traversal, with no manual cross-team merge step required.
- **SC-003**: Zero cases in which a contributor can edit a person/couple whose
  `team_id` is not their current team, without going through the contribution
  flow.

## Assumptions

- This spec changes traversal/visibility rules, not the underlying
  `people`/`couples` table shape — no new columns are required beyond what
  spec 001 (Lineage/LineageMembership) already introduces.
- "Reachable through a relationship from something already visible" (FR-003)
  is bounded to direct genealogical links (parent, child, partner) — it does
  NOT mean full cross-team search visibility, which is explicitly governed by
  spec 006 (Global Search) and spec 007 (Privacy).
- Full multi-team collaborative ownership of a single person (joint edit
  rights) is out of scope for the MVP; the contribution/moderation flow
  (spec 008) is the mechanism for a Team Y contributor to propose a change to
  a Team X-owned person.
