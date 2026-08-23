# Phase 0 Research: Read-Only API Layer

## Decision: `routes/api.php` + versioned prefix, no separate API package

- **Decision**: Add a `v1` route group inside the existing (already
  present, per the 000 audit) `routes/api.php`, rather than introducing a
  dedicated API framework/package.
- **Rationale**: `routes/api.php` already exists and is wired via
  `Application::configure()->withRouting(api: ...)` in `bootstrap/app.php`
  (000 audit) — this feature fills an already-provisioned slot, no new
  dependency needed (CLAUDE.md: no dependency changes without approval).
- **Alternatives considered**: A dedicated versioned package/module
  structure — rejected as over-engineering for 5 endpoints with no external
  consumer yet (spec.md Assumptions).

## Decision: parity tests as the primary test strategy, not independent endpoint tests

- **Decision**: Each endpoint's test suite asserts its response equals a
  transform of the same data the corresponding web page renders (e.g.
  descendants endpoint response vs. `DescendantsController`'s Blade-rendered
  data), rather than independently re-deriving "what should this return."
- **Rationale**: SC-001/SC-002 are explicitly parity criteria ("zero fields
  present in one but not the other"). Testing parity directly is a stronger
  guarantee than two independently-written test suites that could both be
  wrong in the same way.
- **Alternatives considered**: Independent endpoint-only tests — rejected,
  wouldn't actually catch a future divergence between web and API privacy
  behavior, which is the exact risk FR-006 exists to prevent.

## Decision: no authentication tier for MVP endpoints

- **Decision**: All five endpoints in this spec are unauthenticated,
  read-only, and privacy-filtered identically to their web counterparts —
  confirmed by spec.md's Edge Cases and Assumptions (no elevated read tier
  in scope).
- **Rationale**: Matches the "no external consumer yet" framing — there is
  no actor today that would need elevated read access this spec doesn't
  already grant to any guest visitor.
- **Alternatives considered**: Sanctum-token-gated elevated tier — spec.md
  explicitly defers this to a post-MVP spec; not built speculatively here.
