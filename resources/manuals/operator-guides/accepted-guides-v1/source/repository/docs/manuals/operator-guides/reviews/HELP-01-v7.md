# HELP-01 v7 Focused Review Candidate

Status: unaccepted candidate; exact-version owner review pending.

Round: 2026-09-08, separate from the prior Sol baseline and rejected pilot.
The owner approved the WF-01 v12 direction for propagation, not automatic
acceptance of every new PDF.

## Exact Artifacts

- [Candidate PDF](../../../../resources/manuals/operator-guides/review-rounds/2026-09-08/HELP-01-problems-v7-draft.pdf) - 1 page(s), 116563 bytes.
- SHA-256: `c2f49d8598dac156047e5826eab553efb6c103b6645702040ec3943dcc6bf668`.
- [Frozen baseline v6](../../../../resources/manuals/operator-guides/checkpoints/2026-09-08-before-audit-revisions/drafts/HELP-01-problems-v6-draft.pdf).
- Generator: `scripts/manuals/generate-guide-followups.mjs HELP-01`.
- Original renderer: `generate-component-followup-guides.mjs` (unchanged).

## Changes And Tradeoffs

Audit references: AUD-17.

Clarify supervisor contact versus Admin reset execution and complete the guide names in problem tiles/footer. Preserve all twelve problem tiles and the general identity stop.

Benefit: make the next action and its conditions clearer while retaining the
recognizable task layout. Cost: some local wording and spacing differ from
the baseline; reviewers should use the exact linked pair rather than infer
approval from the version number.

No new reset authority, digital destination, or support policy is introduced.

## Validation And Decision

Same page count and 0 raster-image placements as the baseline;
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
