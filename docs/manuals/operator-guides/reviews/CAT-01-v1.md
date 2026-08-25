# CAT-01 v1 Review

| Field | Value |
| --- | --- |
| Previous version | None; first generated CAT-01 version |
| Feedback source | Owner request for a complete model/model-number procedure, 2026-08-18 |
| Impact | New five-page detailed administration guide |
| Status | Working draft; awaiting exact-version review |

## Scope

- Start from the dashboard, open Asset modellen, and search before creating.
- Separate the existing-base-model and missing-base-model routes.
- Explain every base-model field that is relevant to the current application.
- Create an exact manufacturer model-number code and a readable variant label.
- Verify the stored identity and hand off specification work to CAT-02.
- State the actual limits of `Kopieer model`; do not imply full duplication.

## Evidence And Artifact

- Generator: `scripts/manuals/generate-catalog-guide-review.mjs`.
- Proof root: `output/manuals/proofs/catalog-guide-review/cat-01-v1`.
- PDF: `output/pdf/CAT-01-model-en-modelnummer-aanmaken-v1-draft.pdf`.
- PDF SHA-256:
  `A8E0DBFD7D47063C55AB174E1A0704C1762614DA6B3FFF90EB74F6D89B8FACCA`.
- Canonical evidence: `CAT-MODEL-LIST-DESKTOP-01`,
  `CAT-MODEL-DETAIL-DESKTOP-01`, `CAT-MODEL-CREATE-DESKTOP-01`, and
  `CAT-MODEL-NUMBER-CREATE-DESKTOP-01`.

## QA

- Five A4 pages at 594.96 x 841.92 points; PDF 1.4.
- Continuous step numbers 1-8 and explicit page handoffs are present.
- Shared component, badge, guide-reference, context-column, title/version, and
  A4-bound checks passed with no reported geometry errors.
- Extracted text contains the expected model-number, Primary, completion, and
  page-label copy and contains no `dev.inbit` reference.
- All five final PDF pages were rasterized with Poppler and inspected at full
  page. Screenshots retain page landmarks; focus marks are centered and fully
  contained; no title, context, footer, or screenshot overlap remains.
- Unsaved form evidence was captured without submitting a model or model
  number. The QR area remains a deliberate draft placeholder.

## Open Review

- Confirm the five-page pacing, field explanations, examples, and split route
  before this exact version can become an internal review candidate.
- CAT-02 is specified but not generated, so the page-5 handoff is documentary
  rather than a currently available operator PDF.
