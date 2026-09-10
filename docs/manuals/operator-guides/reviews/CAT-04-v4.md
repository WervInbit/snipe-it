# CAT-04 v4 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/CAT-04-componentdefinities-beheren-v4-draft.pdf) - 6 page(s), 434104 bytes.
- SHA-256: `96e99bbeea19a39b7484aaef5d01f8739e7e092916817a7d06e41be8e6dcb0d2`.
- [Frozen baseline v2](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/CAT-04-componentdefinities-beheren-v2-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs CAT-04`.
- Original renderer: `generate-catalog-guide-review.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-06/13/14/15/18.

Name Add Expected Subcomponent and Add Attribute Contribution before filling rows. Use canonical 02 forms. Replace misleading Enum overlap evidence with a real numeric contribution example and accurate warning explanation. Reopen saved Edit to check values, then return to the original task.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

CAT-04 v3 remains rejected. No verified live numeric hierarchy-warning capture was obtained: page 5 is explicitly a contribution example, not proof of a triggered warning. Add-row buttons may require scrolling below the shown rows. Saved cleanup remains an Admin dependency.

## Validation And Decision

Same page count and 8 raster-image placements as the baseline;
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
