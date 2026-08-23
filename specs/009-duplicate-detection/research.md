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

- **Decision**: `score = nameScore * 0.3 + birthYearScore * 0.7` where
  `nameScore` is a normalized string-similarity measure (e.g. Levenshtein
  ratio, 1.0 for an exact match) between full names, and `birthYearScore`
  is `1.0` if both years match exactly, decaying linearly to `0.0` at a
  **40-year** gap (`birthYearScore = max(0, 1 - gapInYears / 40)`), and
  `0.5` (neutral, lower-confidence) if either birth year is unknown.
- **Correction (post-`/speckit-analyze`)**: the original 0.6/0.4 weighting
  with a 20-year decay window had a mathematical floor of `0.6` for any
  exact name match (`nameScore=1.0`, `birthYearScore=0`) — meaning no
  birth-year gap, however large, could ever bring the score below `0.6`.
  This made the 20-year "no warning" (SC-002) and 40-year "low score" (User
  Story 3, Acceptance Scenario 1) requirements mathematically unreachable.
  The corrected weights/window give an exact-match floor of `0.3` (name
  weight alone) and these checkpoints: gap=0 → `1.0` (mandatory
  acknowledgment gate, ≥0.75); gap≈14 years → crosses below the `0.75`
  gate; gap=20 years → `0.65` (low-confidence band, `0.4`–`0.75` — no
  *mandatory* warning, satisfying SC-002's "no warning is shown"); gap=40
  years → `0.3` (below the `0.4` low-confidence floor — not shown at all,
  satisfying Acceptance Scenario 1's "score low enough that no warning is
  shown").
- **Rationale**: Directly derived from spec 009's Acceptance Scenarios
  (User Story 3): same name + matching year → high score; same name + 40
  years apart → low score, below the display floor. The 40-year linear
  decay window (rather than 20) is what makes a 20-year gap and a 40-year
  gap land in genuinely different bands instead of both hitting the same
  floor value.
- **Alternatives considered**: A learned/ML similarity model — explicitly
  rejected by spec.md's Assumptions ("advanced probabilistic/ML matching is
  explicitly out of scope"). Keeping the original 0.6/0.4 weights but
  removing the 20-year floor clamp (letting `birthYearScore` go negative)
  — rejected as needlessly non-intuitive; a score should stay in `[0, 1]`.

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
