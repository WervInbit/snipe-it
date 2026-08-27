# CAT-00 v6 Review

| Field | Value |
| --- | --- |
| Previous version | v5 working draft |
| Generated | 2026-08-27 |
| PDF | `output/pdf/CAT-00-catalogus-begrijpen-v6-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Page 1 relationship-map correction |
| Status | Superseded working draft; v7 is active |

## Correction

- Replace the page 1 three-column lanes with a connected relationship graph.
- Show `Basismodel -> Modelnummer -> Asset` as the identity relationship.
- Attach direct attribute values and expected components to the model number.
- Show that both a direct attribute value and a component definition use a
  reusable attribute definition.
- Show that an expected component references a reusable component definition.
- Render the physical Asset and Placed Component in orange. The legend defines
  orange as an instance: one actual physical device or part.
- Connect a Placed Component to both its component definition and its physical
  asset without connecting it to Expected Component as a mandatory next step.

## QA

- Eight A4 pages generated with no component or geometry errors.
- PDFInfo confirms eight unencrypted A4 pages.
- Shared guide-component and package validation pass.
- All eight PDF pages were rendered at 180 DPI. Page 1 was inspected at full
  resolution and the complete document was inspected as a contact sheet with
  no clipping, overlap, or broken export state.

## Review Decision

CAT-00 v6 is preserved as review history and was superseded by v7. It does not
replace the portable v4 draft or an accepted guide.
