# CAT-01 v4 Review

| Field | Value |
| --- | --- |
| Previous version | v3 unaccepted working draft |
| Generated | 2026-09-01 |
| PDF | `resources/manuals/operator-guides/drafts/CAT-01-model-en-modelnummer-aanmaken-v4-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Family-plan alignment, global exact-code search, and navigation context |
| Status | Unaccepted working draft; exact-version review pending |

## Rebuild

- Retain the reusable three-route decision from v3: exact code exists,
  Basismodel exists but the code is missing, or the Basismodel is missing.
- Start from the dashboard and show `Instellingen > Asset modellen` with the
  complete navigation landmark.
- Search product and generation in the Basismodel list, then search the full
  printed manufacturer code in the global `Model Numbers` list before any
  create action.
- Preserve the complete manufacturer suffix and separate exact code from the
  human-readable recognition label.
- Keep continuous steps 1 through 8, show both Save actions and the saved
  identity, and route specification work to CAT-02 and physical registration
  to AST-03.

## QA

- Five unencrypted A4 pages generated with no shared-component or page-geometry
  errors.
- All five final page renders were inspected at full-page scale. Navigation,
  search context, focus areas, field labels, Save controls, and final references
  remain visible and inside their frames.
- Extracted text contains `Draft v4` and no development or live URL.
- Portable PDF SHA-256:
  `BFC0C872696D9A35B847A7030384A8E37EE2095D1951B43FB502392EF6F6DD14`.
- The complete package verifier passes against a clean mirror containing the
  exact manifest contents: 73 evidence files, 9 accepted PDFs / 11 pages, 17
  unaccepted PDFs / 28 pages, 2 baselines, and 16 active scripts.
- Direct validation of the live draft directory remains blocked only because
  Foxit holds the superseded CAT-00 v7 copy open as one extra unlisted file.

## Review Decision

CAT-01 v4 is the current portable working draft for exact-version review. It
is explicitly unaccepted and does not change any accepted guide record.
