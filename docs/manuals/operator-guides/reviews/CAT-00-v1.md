# CAT-00 v1 Review

| Field | Value |
| --- | --- |
| Previous version | None; first generated CAT-00 version |
| Feedback source | Owner request for an extensive catalogue-guide foundation, 2026-08-18 |
| Impact | New four-page reference chapter and CAT family foundation |
| Status | Working draft; awaiting exact-version review |

## Scope

- Distinguish base model, exact model number, physical asset, reusable component
  definition, and tracked component.
- Route stable facts, expected parts, part properties, physical exceptions, and
  workflow results to the correct data layer.
- Explain the implemented value precedence and reuse-before-create rule.
- Hand off detailed work to CAT-01 through CAT-06 without duplicating those
  procedures.

## Evidence And Artifact

- Generator: `scripts/manuals/generate-catalog-guide-review.mjs`.
- Proof root: `output/manuals/proofs/catalog-guide-review/cat-00-v1`.
- PDF: `output/pdf/CAT-00-catalogus-begrijpen-v1-draft.pdf`.
- PDF SHA-256:
  `586B3C433F21B842318CB9D51CFDEBA27BEF3E307B657F8BC09882527CE3C904`.
- Canonical evidence: `CAT-MODEL-DETAIL-DESKTOP-01` and
  `CAT-MODEL-SPEC-DESKTOP-01`.

## QA

- Four A4 pages at 594.96 x 841.92 points; PDF 1.4.
- Shared component, badge, guide-reference, context-column, title/version, and
  A4-bound checks passed with no reported geometry errors.
- Extracted text contains the expected concepts, page labels, and completion
  state and contains no `dev.inbit` reference.
- All four final PDF pages were rasterized with Poppler and inspected at full
  page. No clipped screenshot, off-page element, overlap, or unreadable crop
  remains.
- The QR area deliberately says `QR volgt`; a real destination or omission is
  required before third-party approval.

## Open Review

- Confirm that the concept depth is useful to an administrator without
  repeating too much of CAT-01/CAT-02.
- Confirm the source hierarchy and abbreviated precedence wording before this
  exact version can become an internal review candidate.
