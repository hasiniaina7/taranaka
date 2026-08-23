# Feature Specification: Read-Only API Layer

**Feature Branch**: `010-api-layer`

**Created**: 2026-08-23

**Status**: Draft

**Input**: User description: "Expose the genealogy engine's read capabilities (person detail, descendants, ancestors, lineage members, search) as versioned, documented read-only endpoints, so a future decoupled frontend (or other consumer) can be built later without re-deriving business logic, without building that frontend now."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Fetch a person's public data via API (Priority: P1)

An API consumer requests a specific person's public profile data and
receives the same privacy-filtered information a signed-out visitor would see
on the web profile (spec 003).

**Why this priority**: This is the smallest slice that proves the API layer
faithfully mirrors the web behavior (especially privacy filtering) rather
than becoming a second, divergent implementation of the same rules.

**Independent Test**: Request a deceased person's data via the API and
verify the response matches what the public web profile shows; request a
living, non-opted-in person and verify sensitive fields are absent from the
response exactly as they are from the web profile.

**Acceptance Scenarios**:

1. **Given** a deceased person with full data recorded, **When** an API
   consumer requests that person, **Then** the response includes name,
   lifespan, lineages, parents, partners, and children references.
2. **Given** a living, non-opted-in person, **When** an API consumer requests
   that person, **Then** the response omits sensitive fields exactly as the
   public web profile does (spec 007 rule applied identically).

---

### User Story 2 - Fetch descendants/ancestors via API (Priority: P1)

An API consumer requests the descendant or ancestor set for a given person,
with the same generation-bounding behavior as the web explorers.

**Why this priority**: These are the two most computationally distinctive
capabilities of the engine (recursive traversal) — proving they're
API-accessible is what makes "moteur généalogique / API / interface web" a
real, decoupled architecture rather than an aspiration.

**Independent Test**: Request descendants for a person with 3 generations
recorded, with a generation limit parameter, and verify the response matches
the same data the descendant explorer (spec 004) would show for that limit.

**Acceptance Scenarios**:

1. **Given** a person with recorded descendants, **When** an API consumer
   requests `GET /api/persons/{id}/descendants`, **Then** the response
   contains the descendant set, respecting privacy filtering per person.
2. **Given** a generation-limit parameter, **When** included in the request,
   **Then** the response is bounded to that many generations, mirroring spec
   004 FR-005.

---

### User Story 3 - Search and lineage endpoints (Priority: P2)

An API consumer searches for people/lineages by name and fetches a lineage's
member list, mirroring specs 001 and 006.

**Why this priority**: Completes read parity with the public web surfaces,
lower priority than person/descendant/ancestor endpoints because search and
lineage browsing are less structurally distinctive than the recursive
traversal endpoints.

**Independent Test**: Search via `GET /api/search?q=Rakoto` and verify person
and lineage results are distinguishable, matching spec 006 behavior; fetch
`GET /api/lineages/{id}/members` and verify it matches spec 001's member
list.

**Acceptance Scenarios**:

1. **Given** matching people and a matching lineage, **When** an API
   consumer searches, **Then** both result types are present and labeled.
2. **Given** a lineage with members, **When** an API consumer requests its
   members, **Then** the response matches the lineage page's member list
   (spec 001).

---

### Edge Cases

- What happens when a requested person does not exist? → Standard not-found
  response, consistent with spec 003 FR-007 (no enumeration hint).
- What happens when a request omits pagination/limit parameters for a
  large result set? → API MUST apply a sane default cap, mirroring spec
  004/005/006's pagination requirements, rather than returning unbounded
  data.
- What happens when this API is called by an unauthenticated consumer versus
  an authenticated one? → For MVP, all endpoints in this spec are read-only
  and privacy-filtered identically regardless of caller identity — there is
  no elevated/authenticated read tier in scope here (see Assumptions).
- What happens when the underlying data model changes (e.g. spec 002's
  cross-team traversal)? → API responses MUST reflect the same traversal
  rules as the web explorers at all times — this spec is a read projection
  of the same engine, not a separate implementation.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST expose a versioned, read-only endpoint returning a
  single person's privacy-filtered public data.
- **FR-002**: System MUST expose a versioned, read-only endpoint returning a
  person's descendants, honoring the same generation-limit behavior as spec
  004.
- **FR-003**: System MUST expose a versioned, read-only endpoint returning a
  person's ancestors, honoring the same generation-limit behavior as spec
  005.
- **FR-004**: System MUST expose a versioned, read-only endpoint returning
  lineage details and member lists, mirroring spec 001.
- **FR-005**: System MUST expose a versioned, read-only search endpoint
  covering people and lineages, mirroring spec 006.
- **FR-006**: Every endpoint MUST apply the same privacy rule (spec 007) as
  its corresponding web surface — no endpoint may expose more than the
  equivalent public web page.
- **FR-007**: Every list-returning endpoint MUST be paginated or capped.
- **FR-008**: The API MUST be versioned (e.g. a version segment in the route)
  so future breaking changes do not silently break existing consumers.
- **FR-009**: API responses MUST use Eloquent API Resources, per existing
  project convention for API work.

### Key Entities

- **Person**, **Lineage**: existing entities from prior specs; this spec adds
  a serialized, versioned read projection of each, not new stored data.

### Team/Lineage scope interaction *(Constitution Principle VII)*

Every endpoint in this spec applies the exact same cross-team traversal and
scoping rules already defined by specs 002 (traversal), 006 (search
bypasses `team` scope), and 009 (duplicate-check scope, not applicable here).
This spec introduces no new scoping decision of its own — it is a read
projection layer over rules decided elsewhere.

## UI & Interface Requirements *(mandatory)*

This spec's primary consumer is a developer, not an end visitor — but per
Constitution's full-stack requirement, the developer-facing interface MUST
still be specified, not left as "just the endpoints."

### Routes / Pages

- `GET /api/docs` (or equivalent, e.g. within `/back/developer/` alongside
  the existing developer tooling per the 000 audit's `IsDeveloper`
  middleware area) — a browsable, human-readable API reference page.

### Livewire / Blade Components

- `ApiDocsPage` — lists each endpoint (person, descendants, ancestors,
  lineage, search) with its route, parameters, an example request, and an
  example response payload reflecting the privacy-filtered shape (so a
  developer sees firsthand that living-person fields are absent, per
  FR-006).
- Reuses the existing `developer.*` route group's layout/nav shell (already
  present per the 000 audit's `routes/web.php` developer section) rather than
  introducing a separate design system for this one page.

### Key Screen States

- **Endpoint list**: grouped by resource (Person / Descendants / Ancestors /
  Lineage / Search), each entry expandable to show full parameter/response
  detail.
- **Live example**: where feasible, an inline "try it" panel using existing
  seed/demo data so a developer can see a real, privacy-filtered response
  without leaving the docs page.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: For any person, the API response and the public web profile
  expose exactly the same set of fields under the same privacy conditions —
  zero fields present in one but not the other.
- **SC-002**: Descendant/ancestor API responses match the corresponding web
  explorer's data set exactly for the same person and generation limit.
- **SC-003**: All list-returning endpoints respond within acceptable latency
  for up to 500 items per page without requiring the consumer to fetch
  unbounded data.

## Assumptions

- No external consumer exists yet for this API at MVP time — it is built to
  prove read-parity with the web surfaces and to avoid re-deriving business
  logic later, per the product cadrage document's explicit "API en lecture
  seule, sans consommateur externe pour l'instant" decision.
- Write endpoints (creating/editing people, submitting contributions via
  API) are explicitly out of scope for this spec; all writes remain through
  the Livewire web UI and the spec 008 contribution flow for MVP.
- Authentication/token-gated elevated API access (e.g. for a future mobile
  app needing contributor-level writes) is deferred to a post-MVP spec.
