# Phase 0 Research: Duplicate Detection

## Decision: separate retrieval (scope) from scoring (service class)

- **Decision**: `Person::scopeSimilarTo()` continues to do what it already
  does (broad candidate retrieval via name LIKE, per the 000 audit) minus
  its current `team_id` filter (removed/bypassed per FR-003); a new
  `PersonSimilarityScorer` ranks the retrieved candidates using name
  closeness + birth-year proximity.
- **Rationale**: The existing scope is a database query; scoring
  (especially once it accounts for multiple weighted signals) is business
  logic that should be unit-testable without a database round-trip. Keeping
  them separate makes SC-002's false-positive-rate requirement testable in
  isolation with hand-crafted fixtures, not just against seeded data.
- **Alternatives considered**: Push scoring into the SQL query itself
  (weighted `ORDER BY` expression) — rejected: harder to unit test, harder
  to reason about/tune the weighting formula, and no performance
  requirement in the spec justifies the complexity of SQL-side scoring at
  MVP scale.

## Decision: weighting formula (name closeness × birth-year proximity)

- **Decision**: `score = nameScore * 0.6 + birthYearScore * 0.4` where
  `nameScore` is a normalized string-similarity measure (e.g. Levenshtein
  ratio) between full names, and `birthYearScore` is `1.0` if both years
  match exactly, decaying linearly to `0.0` at a 20-year gap (matching
  spec.md SC-002's explicit "20+ years apart → no warning" acceptance
  bound), and `0.5` (neutral, lower-confidence) if either birth year is
  unknown.
- **Rationale**: Directly derived from spec 009's Acceptance Scenarios
  (User Story 3): same name + matching year → high score; same name + 40
  years apart → low score. The 20-year linear decay boundary is chosen to
  satisfy SC-002's explicit numeric example without overfitting to it.
- **Alternatives considered**: A learned/ML similarity model — explicitly
  rejected by spec.md's Assumptions ("advanced probabilistic/ML matching is
  explicitly out of scope").

## Decision: threshold for "requires acknowledgment" (soft-gate, FR-006)

- **Decision**: `score >= 0.75` triggers the UI's required-acknowledgment
  state (spec.md UI section); `score` between a lower bound (e.g. 0.4) and
  0.75 shows as "Correspondance faible" without requiring acknowledgment;
  below that, no candidate is shown at all.
- **Rationale**: Gives SC-001 (95% of true near-duplicates warned) and
  SC-002 (low false-positive rate) two different thresholds to tune
  independently during implementation against real seed data, rather than
  a single binary cutoff.
- **Alternatives considered**: Single threshold — rejected, conflates "show
  a hint" with "require explicit acknowledgment," which the spec's FR-002
  ("clear confidence level, not just binary") explicitly asks to avoid.
