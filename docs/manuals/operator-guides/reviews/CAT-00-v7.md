# CAT-00 v7 Review

| Field | Value |
| --- | --- |
| Previous version | v6 working draft |
| Generated | 2026-08-27 |
| PDF | `output/pdf/CAT-00-catalogus-begrijpen-v7-draft.pdf` |
| Generator | `scripts/manuals/generate-catalog-guide-review.mjs` |
| Impact | Shared semantic-object color rule plus guide-specific visual and flow audit corrections |
| Status | Working draft; exact-version review pending |

## Feedback Source

Owner review requested consistent colors for components and component
definitions, and for assets and asset-specific instances. The preceding v6
audit also identified relationship direction, print-size, screenshot-crop,
precedence, example, and planned-route issues.

## Corrections

- Promote the semantic object-color rule to `components.md`: CAT purple for
  catalogue/model/attribute structures, CMP amber for component definitions,
  expected components, and placed components, and AST green for asset identity
  and asset-specific state.
- Keep definition, expected baseline, and physical record roles explicit in
  labels and fill/border treatment instead of assigning unrelated colors.
- Correct the page 1 Componentdefinitie/Placed Component direction and enlarge
  connector labels. The Asset connector now states that the asset contains the
  placed component.
- Clarify on page 2 that a base model can have several codes and one code can
  be used by several assets.
- Increase page 3 explanatory text, separate the immutable-datatype lifecycle
  note from form fields, add the empty Category Scope meaning, enlarge both
  evidence frames, and color the four value locations by object family.
- Apply component amber throughout page 4 and to expected-component content on
  page 5. Replace the mutable operating-system example with screen size and
  enlarge the page 5 evidence while retaining complete focus targets.
- Apply asset green to page 6, retain component amber for Tracked/Assumed
  component state, and crop 6A/6B to complete recognizable cards without
  unrelated controls or clipped serial text.
- Rebuild page 7 as a semantic-color priority ladder, rename priority 3 to
  `Verwachte componentbijdrage`, and replace the underused example/help areas
  with a visible source sequence and two readable rule cards.
- Mark CAT-02 through CAT-06 as `In voorbereiding` on page 8.

## QA

- Generator syntax and `git diff --check` pass.
- Focused generation reports zero shared-component and page-geometry errors.
- PDFInfo confirms eight unencrypted A4 pages with no unintended pages.
- Shared guide-system and portable-package validation pass: 25 registry
  entries, 72 evidence files, 9 accepted PDFs, and 17 unaccepted draft PDFs.
- Extracted text contains `Draft v7`, required component/asset terminology,
  `Verwachte componentbijdrage`, and `In voorbereiding`; it contains no
  `dev.inbit`, stale `Draft v6`, or the removed operating-system example.
- All eight pages were rendered from the final PDF at 180 DPI and inspected.
  A second focused render confirmed the corrected page 1 legend, complete page
  5 focus targets, and full page 6 tracked serial.

## Remaining Review Boundary

- CAT-02 through CAT-06 remain planned and are visibly marked as unavailable.
- The digital-guide QR remains a labelled draft placeholder.
- v7 remains a working draft until the user accepts this exact PDF. It is now
  portable as an explicitly unaccepted draft; no accepted artifact was
  replaced.

## Review Decision

CAT-00 v7 is the active, portable working draft for exact-version review. It
is not approved.
