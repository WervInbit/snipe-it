# CAT-04 v1 Review

| Field | Value |
| --- | --- |
| Previous version | None; first rendered draft |
| Generated | 2026-09-01 |
| PDF | `resources/manuals/operator-guides/drafts/CAT-04-componentdefinities-beheren-v1-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | New six-page component-definition administration guide |
| Status | Unaccepted working draft; exact-version review pending |

## Build

- Start from `Instellingen > Component Definitions`, compare identity and use
  counts before creating, and distinguish a reusable definition from one
  physical tagged component.
- Explain identity fields without inventing browser-inaccessible tracking or
  placement controls.
- Keep expected-part rows and attribute-contribution rows full width so the
  operator can read complete controls and examples.
- Treat hierarchy overlap as an amber correction route, show Save in context,
  verify the reusable result, and route back to CAT-02, CMP-02, or CAT-05.

## Evidence And QA

- Six canonical sources support labels 1A through 6A. Forms were not
  submitted; 5A is a screenshot-only DOM example of the implemented overlap
  warning.
- Six unencrypted A4 pages generated with no shared-component or page-geometry
  errors.
- All final PDF pages were rendered through Poppler and inspected. The page-2
  identity crop was corrected after raster review; navigation, full rows,
  warning context, Save, result verification, and return routes remain legible
  and inside their frames.
- Extracted text contains `Draft v1`, contains no development URL, and contains
  no stale `parent en child` caption.
- Portable PDF SHA-256:
  `4892FBAED0916F9CEF75BE8A4547BCEDAC291103F63C995D1CB2D01575F5BC10`.
- The package verifier passes against a clean mirror containing the exact
  manifest contents: 85 evidence files, 9 accepted PDFs / 11 pages, 19
  unaccepted PDFs / 39 pages, 2 baselines, and 16 active scripts.
- Direct validation of the live draft directory remains blocked only because
  the superseded CAT-00 v7 PDF is one extra unlisted file held open outside
  this guide build.

## Known Boundaries

- Native English field labels remain visible where they are part of the real
  application; surrounding instructions use plain Dutch operator terms.
- Tracking and placement modes are absent because the current browser form
  does not expose them.
- Activation, deactivation, and removal of saved relationships remain planned
  for CAT-05.

## Review Decision

CAT-04 v1 is the current portable working draft for user inspection. It is
explicitly unaccepted and does not change any accepted guide record.
