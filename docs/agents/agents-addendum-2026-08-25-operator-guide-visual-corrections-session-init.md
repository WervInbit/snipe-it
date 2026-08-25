# Session Init: Operator Guide Visual Corrections

Date: 2026-08-25

## Scope

- Revise AC-02, USR-01 through USR-04, AST-03 through AST-05, and CMP-02.
- Correct focus geometry, layout overflow, warning placement, and reusable
  cross-guide references.
- Clarify AST-04 as the final refurbishment-to-QA handoff and explain the
  purpose of its registered-component check.
- Keep CAT guides and frozen accepted artifacts unchanged.

## Validation Plan

- Parse changed generators and run the manuals test suite.
- Generate versioned review PDFs under `output/pdf/`.
- Validate A4 size, page count, required text, and nonblank raster output.
- Render every changed page and inspect the final PNGs at readable scale.

## Environment Notes

- Worktree contains unrelated application and release changes; do not revert
  or include them in guide-specific changes.
- Current source-of-truth guide code is under `scripts/manuals/`; evidence is
  under `resources/manuals/operator-guides/evidence/`.

## Outcome

- Generated nine corrected draft PDFs across eleven A4 pages: AC-02 v3,
  USR-01 v11, USR-02 v9, USR-03 v3, USR-04 v3, AST-03 v14, AST-04 v5,
  AST-05 v5, and CMP-02 v4.
- Added one controlled USR-03 evidence source after invoking `Genereer`; the
  temporary value remained unsaved and the evidence manifest now contains 71
  hashed sources.
- Corrected the requested focus geometry, inline warning placement, full guide
  references, step-4 caption containment, and AST-04 scope/2B explanation.
- Preserved all accepted PDFs and CAT guide artifacts unchanged.
- After review, promoted exact AST-03 v14 to `Internal review candidate` and
  preserved its unchanged two-page PDF in the accepted package.

## Validation Result

- Generator syntax checks passed.
- Shared guide-system and package verification passed with 71 evidence sources,
  eight frozen accepted PDFs, and two baselines.
- All eleven output pages passed A4/page-count and required-text checks.
- Every exact `output/pdf` artifact was rasterized at 160 DPI and visually
  inspected for clipping, overlap, annotation alignment, and reference
  containment.
