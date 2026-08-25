# Implementation Plan: Public Tree Visualization

**Branch**: `012-public-tree-visualization` | **Date**: 2026-08-23 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/012-public-tree-visualization/spec.md`

## Summary

Upgrade the rendering layer of the existing public descendant tree
(`Livewire\People\Descendants\Tree`, spec 004) and ancestor tree
(`Livewire\People\Ancestors\Tree`, spec 005) from static recursive Blade
partials to an interactive graphical canvas using `family-chart` (vanilla
JS/SVG, MIT). Both Livewire components keep their existing traversal logic
and lazy/progressive-loading strategy unchanged (research.md); they gain
two new payload fields (`photo_url`, `partner_ids`) so the client can render
photos and couple-pairing. A shared Blade component wraps a `wire:ignore`
canvas hydrated by a small Alpine.js component, reused identically by both
trees to avoid duplicating `family-chart` initialization.

## Technical Context

**Language/Version**: PHP 8.4, JavaScript (ES modules via Vite)

**Primary Dependencies**: Laravel 12, Livewire 4, Alpine.js (already
bundled with Livewire), Tailwind CSS 4, TallStackUI; **new**: `family-chart`
(npm, MIT) — single new frontend dependency, approved with the project
owner (constitution's dependency-approval convention).

**Storage**: MySQL 8 (reference); no schema change — `photo_url` and
`partner_ids` are derived at render time from existing `people`/`couples`
columns and the existing `photos` filesystem disk.

**Testing**: Pest feature tests asserting the extended payload shape
(`photo_url`, `partner_ids` present and correct) for both trees, plus a
privacy-suppression test (living/non-opted-in node has `photo_url === null`
regardless of an on-file photo). No new browser/JS test framework is
introduced in this spec — canvas rendering itself is validated manually via
quickstart.md; payload correctness (what feeds the canvas) is what Pest
covers, consistent with existing spec 004/005 test scope (PHP-side
correctness, not pixel-level rendering).

**Target Platform**: Existing Docker stack, monolith; desktop + mobile web.

**Project Type**: Web application (monolith), presentation-layer change.

**Performance Goals**: Per spec SC-001/SC-003 — smooth pan/zoom across 3+
generations, usable on a mid-range mobile device. `family-chart` renders to
SVG client-side; no new server round-trips are introduced beyond the
existing branch-expand Livewire actions (spec 004/005 behavior unchanged).

**Constraints**: MUST NOT modify `app/Queries/*` or the core traversal
logic in `BuildDescendantNodes`/`Ancestors\Tree::loadBranch()` beyond adding
the two new derived fields (Constitution Principle VI). MUST NOT touch any
back-office/moderation/developer view or controller (FR-007).

**Scale/Scope**: Two existing Livewire components extended (not replaced),
one new shared Blade component, one new JS entry point, one new npm
dependency. No new routes, no new controllers.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-checked after Phase 1 design.*

| Principle | Status | Note |
|---|---|---|
| I. One Person, One Entity | PASS (N/A) | Presentation-only; no new Person rows. |
| II/VIII. Privacy | PASS | `photo_url` forced `null` for privacy-limited living nodes (FR-004/FR-010); no new field bypasses spec 007. |
| III. Laravel/Livewire Stack | PASS | `family-chart` is a vanilla-JS rendering library hydrated inside Livewire/Alpine, not a competing SPA framework — stack stays Laravel/Livewire per Principle III. |
| IV. MySQL 8 | PASS (N/A) | No schema/query change. |
| V. Test-First | GATE | New Pest tests for the two extended payload fields, written before implementation, per spec 004/005 precedent. |
| VI. Reuse Recursive-Query Engine | PASS | `app/Queries/*` and the existing traversal methods are not modified; only two derived fields are added to their already-existing output arrays. |
| VII. Teams Repositioned | PASS (N/A) | No interaction with the `team` global scope beyond what specs 004/005 already do. |
| VIII. No Sensitive Data Without Privacy Rule | PASS | See Privacy row above. |
| IX. Migrations | N/A | No migration. |
| Full-stack specs | PASS | UI & Interface Requirements section present in spec.md. |
| Dependency approval | PASS | `family-chart` explicitly approved by the project owner during spec clarification. |

No violations — Complexity Tracking table is empty.

## Project Structure

### Documentation (this feature)

```text
specs/012-public-tree-visualization/
├── plan.md              # This file
├── research.md           # Phase 0 output
├── data-model.md          # Phase 1 output
├── quickstart.md          # Phase 1 output
└── tasks.md               # Phase 2 output (/speckit-tasks)
```

### Source Code (repository root)

```text
package.json                                    # + "family-chart" devDependency (or dependency, Vite-bundled either way)
resources/js/
├── app.js                                       # imports the new family-tree entry so Vite bundles it
└── family-tree.js                               # NEW — Alpine.js component wrapping family-chart:
                                                  #   init(payload, rootProfileUrlTemplate), handles
                                                  #   click-vs-drag (FR-011), re-renders on Livewire
                                                  #   payload updates (wire:ignore boundary)

resources/css/app.css                            # + minimal family-chart node/canvas theme overrides
                                                  #   mapped to existing Tailwind/TallStackUI tokens (FR-002)

resources/views/components/
└── family-tree-canvas.blade.php                 # NEW — shared wire:ignore canvas shell + Alpine
                                                  #   x-data binding, used by both trees below

app/Actions/
└── BuildDescendantNodes.php                     # + photo_url, partner_ids fields (no traversal change)

app/Livewire/People/Descendants/
├── Tree.php                                     # tree() payload gains photo_url/partner_ids per node
└── (blade updated to render <x-family-tree-canvas> instead of the recursive partial)

app/Livewire/People/Ancestors/
├── Tree.php                                     # buildNode() gains photo_url/partner_ids per node
└── (blade updated to render <x-family-tree-canvas>)

resources/views/livewire/people/descendants/
├── tree.blade.php                                # replaces `partials/node` include with <x-family-tree-canvas>
resources/views/livewire/people/ancestors/
├── tree.blade.php                                # same swap
└── partials/tree-node.blade.php                  # retained only as the no-JS/empty-state fallback markup source

tests/Feature/PublicTreeVisualization/
├── DescendantTreePayloadIncludesPhotoAndPartnersTest.php
├── AncestorTreePayloadIncludesPhotoAndPartnersTest.php
├── LivingNodePhotoSuppressedTest.php
└── BackOfficeViewsUnaffectedTest.php              # scope-boundary regression guard (FR-007)
```

**Structure Decision**: Extend the two existing tree Livewire components and
their `BuildDescendantNodes`/`buildNode()` payload builders in place, rather
than introducing a new component tree — this is a rendering-layer upgrade
of specs 004/005, not a new feature surface (no new routes/controllers).
The shared `<x-family-tree-canvas>` Blade component is the only new
structural piece, added specifically to avoid duplicating `family-chart`
initialization/theme logic between the two trees.

## Complexity Tracking

*No Constitution Check violations — table intentionally empty.*
