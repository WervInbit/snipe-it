# CAT-05 v1 Review Record

Date: 2026-09-15  
Decision: Working draft; not accepted.

Owner follow-up (2026-09-15): Needs revision for content alignment. The
[cross-guide audit](USR-05-CAT-05-CAT-06-content-audit-2026-09-15.md) records
C5.1-C5.5 and the v2 content brief. Preserve the v1 PDF/hash; generation checks
below are historical validation, not evidence that the content passed review.

## Artifact

- PDF: `resources/manuals/operator-guides/drafts/cat-05-varianten-en-lifecycle-beheren-v1-draft.pdf`
- SHA-256: `e8b8e2baff42956c01a060b3f01589e6722ebdce4512446ef0cd1c0910348921`
- Pages: 5 A4 portrait pages.
- Generator: `scripts/manuals/generate-planned-guide-drafts.mjs`.
- Layout: `branching-admin-maintenance`; pages 2 through 5 are alternatives.

## Review Performed

- Verified application behavior and permission boundaries against the existing
  catalog tests, policies, guide plan, and live Admin views.
- Captured saved-attribute and expected-component cleanup controls read-only;
  no row was removed and no form was saved.
- Preserved ordinary creation in CAT-01 and avoided inventing a browser Delete
  action for component definitions.
- Ran guide-definition, shared-component, SVG geometry, page-count, PDF metadata,
  A4-size, text, and full-page raster checks.

## Open Before Acceptance

- An Admin owner must execute each lifecycle/cleanup branch with disposable
  controlled records and verify the documented downstream checks.
- Decide whether a later version uses the proposed narrower title
  `Catalogusstatus en opschoning beheren`.
- Replace the proof QR placeholder only after the digital-guide target is set.
