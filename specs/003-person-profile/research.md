# Phase 0 Research: Public Person Profile

## Decision: Separate guest controller/component, not a shared one with the authenticated view

- **Decision**: `Front\PersonProfileController` + `PublicProfile` Livewire
  component are new and separate from `Back\PeopleController@show`.
- **Rationale**: The authenticated show route today runs inside the
  `auth:sanctum` + team-scope middleware stack and assumes a permissioned
  viewer; retrofitting a privacy filter onto it risks a future change to
  the authenticated path silently reopening a public leak. A separate,
  narrowly-scoped guest path is easier to audit end-to-end for FR-003.
- **Alternatives considered**: One shared component with a `$isPublic` flag
  branching internally — rejected: mixes two different trust boundaries in
  one code path, exactly the pattern that produces privacy bugs.

## Decision: Minimal `PersonPrivacy` helper now, formalized by spec 007 later

- **Decision**: Implement the smallest possible living/not-living field
  filter in this spec (`app/Support/PersonPrivacy.php`), used by
  `PublicProfile` and `PrivacyBanner`, and treat it as the seed that spec
  007 formalizes (opt-in/opt-out, auditability) rather than replaces.
- **Rationale**: Constitution Principle VIII is explicit — no public
  surface may exist before a privacy rule exists, even a provisional one.
  Sequencing 003 strictly after 007 would delay the first public page
  indefinitely; sequencing 007 strictly after 003 would mean 003 ships
  without protection. A minimal, forward-compatible helper resolves the
  ordering conflict.
- **Alternatives considered**: Block spec 003 entirely until 007 ships —
  rejected, unnecessarily serializes two specs that are independently
  valuable and independently testable per Spec Kit's own story-independence
  principle.

## Decision: `PrivacyBanner` as a Blade view component, not a Livewire component

- **Decision**: `PrivacyBanner` is a stateless Blade component
  (`<x-privacy-banner>`), not a full Livewire component.
- **Rationale**: It has no interactivity of its own (just a conditional
  render based on a passed-in boolean) — a Livewire component would add
  needless request overhead for a static banner reused across four future
  specs.
- **Alternatives considered**: Livewire component — rejected per above;
  revisit only if a future spec needs the banner to be independently
  reactive (no such requirement exists in specs 003–010 today).
