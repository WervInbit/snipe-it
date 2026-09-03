# CAT-01 v5 Review

| Field | Value |
| --- | --- |
| Previous version | v4 unaccepted working draft |
| Generated | 2026-09-03 |
| PDF | `resources/manuals/operator-guides/drafts/CAT-01-model-en-modelnummer-aanmaken-v5-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Duplicate-route clarity, action naming, sparse-text sizing, and default-configuration label rule |
| Status | Unaccepted working draft; exact-version review pending |

## Rebuild

- Increase body text in sparse step and route regions instead of retaining the
  small type needed by denser guides.
- Name step 2 as duplicate prevention and state that `2B` requires the
  separate `Instellingen > Model Numbers` page rather than another search on
  `Asset modellen`.
- Name route C as the action `Maak een nieuw Basismodel` and send the reader
  explicitly to numbered step 4.
- Define the model-number label as the default processor, RAM, and storage for
  that exact printed code. A different configuration on one physical asset is
  recorded on that asset and does not create another model number.
- Preserve the v4 evidence set, continuous steps 1 through 8, both Save
  actions, final identity verification, and CAT-02/AST-03 handoffs.

## QA

- Five unencrypted A4 pages generated with no shared-component or page-geometry
  errors.
- All five final page renders were inspected at full-page scale. Enlarged text,
  route labels, screenshot badges, field summaries, focus marks, and footer
  references remain clear and inside their frames.
- Regenerated CAT-01 v4 with its original date. All five 150-DPI rasterized
  pages match the existing v4 reference byte-for-byte.
- Extracted text contains `Draft v5` and no development or live URL.
- Portable PDF SHA-256:
  `39BFAF5725DBCB41308470BF1C13D5E1676E4B01364C646FE659230E77E8AB8F`.
- The complete package verifier passes against a clean mirror containing the
  exact manifest contents: 85 evidence files, 9 accepted PDFs / 11 pages, 19
  unaccepted PDFs / 39 pages, 2 baselines, and 16 active scripts.

## Review Decision

CAT-01 v5 is the current portable working draft for exact-version review. It
is explicitly unaccepted and does not change any accepted guide record.
