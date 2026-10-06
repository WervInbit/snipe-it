# USR-05 v1 Review Record

Date: 2026-09-15  
Decision: Working draft; not accepted.

Owner follow-up (2026-09-15): Needs revision for content alignment. The
[cross-guide audit](USR-05-CAT-05-CAT-06-content-audit-2026-09-15.md) records
U1-U3 and the v2 content brief. Preserve the v1 PDF/hash; generation checks
below are historical validation, not evidence that the content passed review.

## Artifact

- PDF: `resources/manuals/operator-guides/drafts/usr-05-groepen-beheren-v1-draft.pdf`
- SHA-256: `7cd6c0b6ae1e7ab1bb8004165fe6de6f40635e079c2867fe53aeb744dd46807c`
- Pages: 2 A4 portrait pages.
- Generator: `scripts/manuals/generate-planned-guide-drafts.mjs`.
- Layout: `stacked-step-flow` with a two-sided continuation.

## Review Performed

- Captured the real list, create, permission, save, and group-detail routes
  without submitting a form.
- Confirmed the USR-02 boundary: USR-05 defines a reusable group; USR-02 assigns
  it to one user.
- Ran guide-definition, shared-component, SVG geometry, page-count, PDF metadata,
  A4-size, text, and full-page raster checks.

## Open Before Acceptance

- A Superadmin must review the minimum-rights wording.
- Assign the group to one disposable controlled user and verify both required
  and prohibited routes.
- Replace the proof QR placeholder only after the digital-guide target is set.
