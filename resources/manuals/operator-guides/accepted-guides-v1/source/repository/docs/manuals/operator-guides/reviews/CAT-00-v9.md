# CAT-00 v9 Review

| Field | Value |
| --- | --- |
| Previous version | v8 unaccepted working draft |
| Generated | 2026-09-03 |
| PDF | `resources/manuals/operator-guides/drafts/CAT-00-catalogus-begrijpen-v9-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Diagram connector and vertical-alignment correction |
| Status | Unaccepted working draft; exact-version review pending |

## Correction

- Preserve the six-part content, evidence, terminology, and routing from v8.
- Remove opaque label masks that visually interrupted connectors on page 1.
- Connect the model number to both direct values and expected components.
- Keep connector labels clear of their lines and make every arrow terminate at
  its intended node.
- Give the page 3 Attribuutdefinitie rows enough height for their title and
  datatype text, and align the one-to-many branch with all three rows.
- Attach the page 4 `Verwijderd (Removed)` explanation to the physical-state
  branch instead of leaving it as a detached white block.

## QA

- Six unencrypted A4 pages generated with no shared-component or page-geometry
  errors.
- All six final page PNGs were inspected at full-page scale. No connector is
  masked by text, every branch reaches its node, and the page 3 definition rows
  remain within their frames.
- The retained v8 generator branch was regenerated and all six rasterized pages
  match the committed v8 PDF pixel-for-pixel at 150 DPI.
- Extracted text contains `Draft v9` and no development or live URL.
- Portable PDF SHA-256:
  `A7E0D6C7C554A74C2B5161F1BABBC1D77DCBC8EFE47FCBB7F2DB39F9EF8127AE`.
- The manifest-only package verifier passes with 85 evidence files, 9 accepted
  PDFs / 11 pages, 19 unaccepted PDFs / 39 pages, 2 baselines, and 16 active
  scripts. Direct live-root verification waits for the external PDF reader to
  release the superseded v7/v8 local copies.

## Review Decision

CAT-00 v9 is the current portable working draft for exact-version review. It
is not approved and does not change any accepted guide record.
