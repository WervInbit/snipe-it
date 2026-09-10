# CMP-01 v6 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/CMP-01-install-existing-v6-draft.pdf) - 1 page(s), 252875 bytes.
- SHA-256: `4be1ad1f00e118bd257e6fc9c92fc647e61ee83bdf5677a3a1915f45c82689df`.
- [Frozen baseline v5](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/CMP-01-install-existing-v5-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs CMP-01`.
- Original renderer: `generate-component-guide-review.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-17.

Use the complete names of the destination guides in prerequisite/footer chips; retain the four-step tracked-component flow and all images/help.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

This is a small reference consistency change. Existing tray and condition policies are preserved.

## Validation And Decision

Same page count and 4 raster-image placements as the baseline;
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
