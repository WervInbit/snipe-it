# CAT-00 v5 Review

| Field | Value |
| --- | --- |
| Previous version | v4 working draft |
| Generated | 2026-08-27 |
| PDF | `output/pdf/CAT-00-catalogus-begrijpen-v5-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Reviewed conceptual and visual correction pass |
| Status | Superseded working draft; v7 is active |

## Correction

- Replace the separate definition and identity diagrams on page 1 with one
  complete catalogue map: Basismodel -> Modelnummer -> Asset, plus the
  attribute and component definition/application lanes.
- Vertically centre the reusable-building-block text and remove the cramped
  fourteen-millimetre cards.
- Remove visual 2B. Category and manufacturer were selected only to explain
  Basismodel metadata and distracted from the identity distinction.
- Enlarge visual 2A so the Basismodel breadcrumb and complete model-number row
  remain recognizable in one screenshot.
- Use the exact current attribute-form labels and pair every field with a
  concrete example. Datatype examples now use the values visible in the
  system: `Bool`, `Int`, `Decimal`, `Enum`, and `Text`.
- Present a component definition as a reusable group of attribute values that
  branches into an `Expected Component` on a model-number baseline or a
  `Placed Component` on a physical asset. These are uses of one definition,
  not three mandatory consecutive steps.
- Replace the small page 6 comparison with a readable baseline-versus-asset
  table and two large evidence crops. The example matches the visible 8 GB
  tracked RAM and 256 GB assumed storage; the 16 GB upgrade remains explicitly
  hypothetical.

## QA

- Eight A4 pages generated with no component or geometry errors.
- Shared guide-component tests pass.
- PDFInfo confirms eight unencrypted A4 pages.
- Pypdf extraction confirms all chapter headings, the reviewed UI field names,
  `Expected Component`, `Placed Component`, and the evidence-aligned page 6
  captions. Removed 2B copy and development URLs are absent.
- All eight final PDF pages were rendered from the PDF at 180 DPI and visually
  inspected without clipping, overlap, or export-only layout defects.

## Review Decision

CAT-00 v5 is preserved as review history and was superseded by v6, then v7. It
did not replace the portable v4 draft or any accepted guide.
