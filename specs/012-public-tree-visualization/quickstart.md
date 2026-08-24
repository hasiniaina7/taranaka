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
   instantly (no network activity): the whole bounded tree is already
   present client-side (research.md Decision 2), so expand/collapse is
   handled entirely by family-chart.
5. Click (no drag) a node card — browser navigates to that person's public
   profile (spec 003).
6. Confirm a couple in the seeded data renders as two cards joined by a
   union line, with shared children attached below the pair. A partner who
   is not itself a blood descendant (e.g. married in) still renders — its
   card is built from the `partners[].name`/`photo_url` carried on the
   node that references it (data-model.md), not from a separate payload
   entry.

## Validate User Story 2 — ancestor tree

Repeat steps 1, 2, 3, 5, 6 above against `/p/{person}/ancestors` for a
person with recorded ancestors. Step 4 differs here: only the loaded
branches are known server-side (spec 005's per-branch loading is
unchanged), so clicking a not-yet-loaded parent slot *does* trigger a
Livewire `toggleBranch` call (network tab shows the XHR) before the canvas
re-renders with the fresh branch.

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
