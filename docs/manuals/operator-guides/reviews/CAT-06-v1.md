# CAT-06 v1 Review Record

Date: 2026-09-15  
Decision: Verification-only working draft; not accepted.

Owner follow-up (2026-09-15): Needs revision for content alignment. The
[cross-guide audit](USR-05-CAT-05-CAT-06-content-audit-2026-09-15.md) records
C6.1-C6.3 and the v2 content brief. Preserve the v1 PDF/hash; generation checks
below are historical validation, not evidence that the content passed review.

## Artifact

- PDF: `resources/manuals/operator-guides/drafts/cat-06-catalogus-controleren-en-bronnen-v1-draft.pdf`
- SHA-256: `1eee3e99c7bb7dfe789688abfe8b1e2f34023334aa6dd77b9f7f570f4fcad59a`
- Pages: 3 A4 portrait pages.
- Generator: `scripts/manuals/generate-planned-guide-drafts.mjs`.
- Layout: `reference-checklist`.

## Review Performed

- Reused the real owner-supplied device photo and cropped the physical HP label
  showing product family and exact Product ID.
- Limited v1 to evidence selection, identity verification, fact classification,
  and routing into CAT-01 through CAT-05.
- Explicitly excluded a durable source-recording instruction because the current
  catalogue UI has no dedicated source field.
- Ran guide-definition, shared-component, SVG geometry, page-count, PDF metadata,
  A4-size, text, and full-page raster checks.

## Open Before Acceptance

- The catalogue owner must approve the authoritative fallback-source policy.
- Operations/product must add a source field or approve a durable convention if
  traceability inside Snipe-IT is required.
- Replace the proof QR placeholder only after the digital-guide target is set.
