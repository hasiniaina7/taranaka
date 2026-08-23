# Quickstart: Public Tree Visualization

## Prerequisites

- Docker stack running: `vendor/bin/sail up -d`
- Frontend deps installed with `family-chart` added:
  `vendor/bin/sail npm install`
- Vite dev server running: `vendor/bin/sail npm run dev`
  (or `vendor/bin/sail npm run build` for a production bundle check)
- A seeded person with at least 3 recorded generations of descendants and
  at least one recorded couple/remarriage, and a separate person with 3+
  generations of ancestors (reuse existing spec 004/005 test fixtures /
  factories if available).

## Validate User Story 1 — descendant tree

1. Visit `/p/{person}/descendants` for a seeded person with descendants.
2. Confirm the canvas renders a graphical tree (not stacked HTML rows),
   themed with the site's existing colors/dark-mode.
3. Drag to pan, scroll/pinch to zoom — canvas responds smoothly.
4. Click a collapsed branch's expand affordance — next generation appears
   without a full page reload (network tab shows a Livewire XHR, not a
   document navigation).
5. Click (no drag) a node card — browser navigates to that person's public
   profile (spec 003).
6. Confirm a couple in the seeded data renders as two cards joined by a
   union line, with shared children attached below the pair.

## Validate User Story 2 — ancestor tree

Repeat steps 1–6 above against `/p/{person}/ancestors` for a person with
recorded ancestors.

## Validate User Story 3 — mobile

1. Open either tree URL with browser dev tools set to a mobile viewport
   (e.g. 390×844).
2. Confirm no horizontal scroll on the page itself.
3. Confirm pinch-to-zoom / drag-to-pan work on the canvas via touch
   emulation.

## Validate privacy (FR-004/FR-010)

1. Open a descendant or ancestor tree that includes a living, non-opted-in
   person (spec 007).
2. Confirm that node shows the existing privacy-limited treatment and no
   photo, even if that person has one on file.

## Validate scope boundary (FR-007)

1. Diff the changed files for this feature.
2. Confirm no path under `resources/views/**/back/**`,
   `resources/views/**/developer/**`, `resources/views/**/moderation/**`,
   or their corresponding Livewire/controller namespaces appears in the
   diff.
