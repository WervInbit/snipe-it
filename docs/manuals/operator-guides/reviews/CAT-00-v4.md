# CAT-00 v4 Review

| Field | Value |
| --- | --- |
| Previous version | v3 working draft |
| Generated | 2026-08-25 |
| PDF | `resources/manuals/operator-guides/drafts/CAT-00-catalogus-begrijpen-v4-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Complete reference-chapter and mental-model rewrite |
| Status | Working draft; exact-version review pending |

## Correction

- Expand CAT-00 from four pages to eight so the core concepts are explained
  before the reader is routed to a task guide.
- Remove the leaked layout prompt from the three-column device examples.
- Define Basismodel, model number, and physical asset as different identities.
- Distinguish an attribute definition from the value stored at a model number,
  component definition, permitted asset override, or workflow result.
- Distinguish a reusable component definition, an expected model-number
  component, and a placed physical component on one asset.
- Explain the model-number baseline and why a component upgrade does not create
  a new manufacturer model number.
- Explain the operator-facing effective-value order without exposing code terms.
- Put the CAT-01 through CAT-06 route map after the conceptual explanation.

## QA

- Eight A4 pages generated with no component or geometry errors.
- Six registered evidence sources are reused by stable IDs.
- Shared component tests and the complete evidence/accepted-package validator
  pass.
- PDFInfo confirms eight unencrypted A4 pages.
- Extracted-text checks confirm all eight chapter headings and the complete
  CAT-01 through CAT-06 guide names; no forbidden operator term or development
  URL is present.
- All eight final PDF pages were rendered at 180 DPI and visually inspected
  without clipping, overlap, or export-only layout defects.

## Review Decision

CAT-00 v4 is a working draft for exact-version review. It does not replace an
accepted guide version until the owner explicitly accepts this PDF.
