# Operator Guide Review Records

## Owner Clearance For User Review - 2026-09-10

The owner cleared the ten current changed versions in the v5 pair for review
by other users. They are now Internal review candidates; the exact PDFs stay
unchanged. Use the [dated clearance and hashes](owner-review-readiness-2026-09-10.md)
and current-guide-selection-v4.json for current status. Earlier four exact
acceptances remain; the separate prototype is outside this clearance.
Other-user feedback and third-party approval are still pending. Earlier
generation-time draft/pending wording below is historical for these versions.

## Implemented Owner Corrections - 2026-09-10

Use the [correction review and plain v4 pair](owner-corrections-2026-09-10.md)
for current review: ten revised guides, 22 guides total, previous 48 pages and
current 47 pages. USR-04 v6 is disable-login only and requires a new review.
Earlier exact acceptances remain. The field-to-result prototype is separate.
Testing completed is the preferred future QA label; no Awaiting approval
state or application status change is included. Older sections below are history.

- [Owner review and action list - 2026-09-10](owner-review-todos-2026-09-10.md):
  seven local corrections, USR-04 v5 explicitly not accepted, QA status and
  catalogue wording decisions, plus a deferred visual map and learning-order review.

Focused review records preserve the small corrections made while a guide is
iterated. They complement guide specifications; they do not replace them.

## Current Acceptance And Review Bundle - 2026-09-10

- [Current set review](current-set-review-2026-09-10.md): all 22 current guides
  in one versioned PDF; exact acceptance of AC-01 v9, AC-02 v4, AST-03 v15,
  and AST-04 v6, with hashes and preserved earlier versions.

## Existing Round / New Audit Revision Round

- [All-guide comparison - 2026-09-08](guide-set-comparison-2026-09-08.md):
  twenty new candidates following the owner-reviewed WF-01 v12 approach,
  with old/new PDF pairs. WF-01 v12 and CMP-04 v6 are retained unchanged.

- [Frozen baseline - 2026-09-08](baseline-2026-09-08.md): unchanged copies of
  the 9 internally accepted PDFs and 20 drafts, exact versions, checksums,
  and source inputs for comparison or recovery.
- [New audit revision round - 2026-09-08](audit-revision-round-2026-09-08.md):
  separate candidate ledger and per-change tradeoffs. WF-01 v11, USR-04 v4,
  and CAT-04 v3 plus a comparison PDF are available. Existing acceptance
  remains unchanged; the owner rejected this batch's design direction.
- [Pilot direction feedback - 2026-09-08](2026-09-08-pilot-direction-feedback.md):
  retain the baseline screenshots, hints, help, and task-dependent layout;
  improve the WF-01 choice block within its existing step structure.
- [WF-01 v12](WF-01-v12.md): generated from v10, with all five screenshots
  and four help items preserved and a clearer step-3 choice; awaiting review.

---

## Current Set Audit

- [2026-09-08 independent usability and consistency audit](2026-09-08-independent-usability-audit.md):
  all 22 current generated guides / 48 pages, shared rules, high-risk local
  application behavior, and missing guide dependencies. Records 22 findings
  and a proposed correction order; no artifact acceptance changed.
- [Exact audit evidence](2026-09-08-audit-evidence.json): PDF versions, hashes,
  page counts, measured type sizes, validation outcomes, and limitations.

## Version Reviews

Create one file per reviewed version using `<CODE>-vN.md` and record:

- artifact status;
- source version and output paths;
- accepted content and visual corrections;
- open corrections or evidence gaps;
- feedback promoted to a guide, family, or global rule;
- feedback source and impact classification;
- previous version and complete affected-guide list for shared changes;
- layout recipe and step-pattern changes;
- PDF, page-count, text, geometry, and visual QA results.

When the next version is created, retain the previous record. Do not rewrite
history to make an older artifact appear compliant with a newer rule.

Use [maintenance.md](../maintenance.md) for the full change workflow. A global,
family, recipe, evidence, or policy change requires a review record for every
visibly affected guide version, not only the first representative proof.
