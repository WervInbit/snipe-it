# CAT-02 v2 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/CAT-02-modelspecificatie-opbouwen-v2-draft.pdf) - 6 page(s), 783022 bytes.
- SHA-256: `13c8939cbe9a72134983161553c1f0696ac57aff8eca7b1fe99325efb6a2be44`.
- [Frozen baseline v1](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/CAT-02-modelspecificatie-opbouwen-v1-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs CAT-02`.
- Original renderer: `generate-catalog-guide-review.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-15/20.

Page 2 records unsaved input before leaving to create a definition, then reopens the same Edit Spec and resumes page 3/4. Page 5 labels the conflict as a separate example and routes saved-row deletion to an Admin.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

This preserves six pages and twelve images. A single continuous creation/conflict/save dataset remains a future evidence improvement.

## Validation And Decision

Same page count and 12 raster-image placements as the baseline;
help headings retained. Rendered pages visually inspected. PDF text bounds,
version/date, image inventory, and extracted text changes checked. Image count
is not a claim that every crop or source is identical. See the set validation
for the deliberate source/crop and spacing exceptions.

No physical A4 print, first-time-user trial, production configuration check,
or fresh complete live workflow was performed. No new acceptance is recorded.
Decision: pending. Reviewer/date: not yet recorded.

To reject: leave current manifests unchanged and continue from the frozen
baseline. To accept: record the exact version/hash and selection separately;
keep earlier files. See [set comparison](guide-set-comparison-2026-09-08.md).
